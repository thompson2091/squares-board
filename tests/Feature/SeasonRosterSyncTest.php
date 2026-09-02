<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Season;
use App\Models\User;
use App\Services\SeasonService;
use App\Services\SeasonStandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The roster is the source of truth for who owns which square. Changing it has
 * to reach every week that hasn't been played yet - and no further.
 */
class SeasonRosterSyncTest extends TestCase
{
    use RefreshDatabase;

    private function startedSeason(User $owner, User $player, int $weeks = 3): Season
    {
        $season = app(SeasonService::class)->create([
            'name' => 'Roster Sync Pool',
            'slug' => 'roster-sync-pool',
            'description' => null,
            'total_weeks' => $weeks,
            'price_per_square' => 500,
            'max_squares_per_user' => 100,
            'number_draw_mode' => Season::DRAW_UPFRONT,
            'is_public' => false,
            'payment_instructions' => null,
        ], $owner);

        $roster = $season->roster();

        foreach ([['Q1', 2000], ['final', 8000]] as [$quarter, $amount]) {
            $roster?->payoutRules()->create([
                'quarter' => $quarter,
                'payout_type' => 'percentage',
                'amount' => $amount,
                'winner_type' => 'primary',
            ]);
        }

        $roster?->squares()->get()->each(fn ($square) => $square->claim($player));

        app(SeasonService::class)->start($season->fresh());

        return $season->fresh();
    }

    public function test_claiming_after_the_season_starts_propagates_to_every_week(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $newcomer = User::factory()->create();

        $season = $this->startedSeason($owner, $player);

        // An admin hands square (2,2) to someone else mid-season.
        $season->roster()?->getSquareAt(2, 2)?->update(['user_id' => $newcomer->id]);

        foreach ($season->fresh()->weekBoards as $week) {
            $this->assertSame(
                $newcomer->id,
                $week->getSquareAt(2, 2)?->user_id,
                "Week {$week->week_number} should follow the roster."
            );
        }
    }

    public function test_releasing_a_roster_square_clears_it_on_every_week(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();

        $season = $this->startedSeason($owner, $player);

        $season->roster()?->getSquareAt(5, 5)?->release();

        foreach ($season->fresh()->weekBoards as $week) {
            $this->assertNull($week->getSquareAt(5, 5)?->user_id);
        }
    }

    public function test_renaming_and_marking_paid_both_propagate(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();

        $season = $this->startedSeason($owner, $player);

        $rosterSquare = $season->roster()?->getSquareAt(1, 1);
        $rosterSquare?->update(['display_name' => 'Big Country']);
        $rosterSquare?->markAsPaid();

        foreach ($season->fresh()->weekBoards as $week) {
            $square = $week->getSquareAt(1, 1);
            $this->assertSame('Big Country', $square?->display_name);
            $this->assertTrue($square?->is_paid);
        }
    }

    public function test_a_week_with_winners_is_never_rewritten(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $newcomer = User::factory()->create();

        $season = $this->startedSeason($owner, $player);

        // Play week 1.
        $week1 = $season->weekBoard(1);
        $this->actingAs($owner)->post("/manage/boards/{$week1->uuid}/scores", [
            'quarter' => 'Q1',
            'team_row_score' => 7,
            'team_col_score' => 3,
        ])->assertRedirect();

        $this->assertGreaterThan(0, $week1->fresh()->winners()->count());

        // Now reassign every roster square.
        $season->roster()?->squares()->get()->each(fn ($square) => $square->update(['user_id' => $newcomer->id]));

        $season = $season->fresh();

        // Week 1 keeps its history...
        $this->assertSame($player->id, $season->weekBoard(1)?->getSquareAt(0, 0)?->user_id);

        // ...while the unplayed weeks follow the roster.
        $this->assertSame($newcomer->id, $season->weekBoard(2)?->getSquareAt(0, 0)?->user_id);
        $this->assertSame($newcomer->id, $season->weekBoard(3)?->getSquareAt(0, 0)?->user_id);
    }

    public function test_completed_weeks_are_left_alone(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $newcomer = User::factory()->create();

        $season = $this->startedSeason($owner, $player);
        $season->weekBoard(1)?->update(['status' => Board::STATUS_COMPLETED]);

        $season->roster()?->getSquareAt(4, 4)?->update(['user_id' => $newcomer->id]);

        $season = $season->fresh();

        $this->assertSame($player->id, $season->weekBoard(1)?->getSquareAt(4, 4)?->user_id);
        $this->assertSame($newcomer->id, $season->weekBoard(2)?->getSquareAt(4, 4)?->user_id);
    }

    public function test_standings_add_up_to_the_per_week_winner_payouts(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();

        $season = $this->startedSeason($owner, $player);

        foreach ([1, 2] as $number) {
            $week = $season->weekBoard($number);
            $this->actingAs($owner)->post("/manage/boards/{$week->uuid}/scores", [
                'quarter' => 'Q1',
                'team_row_score' => 14,
                'team_col_score' => 10,
            ])->assertRedirect();
        }

        $season = $season->fresh();
        $standings = app(SeasonStandingsService::class);

        $rows = $standings->forSeason($season);
        $paidOut = $standings->paidOut($season);

        $this->assertSame($paidOut, (int) $rows->sum('total'));
        $this->assertSame($paidOut, $standings->winningsFor($season, $player));

        // One player holds all 100 squares and won both weeks' Q1.
        $this->assertCount(1, $rows);
        $this->assertSame(100, $rows[0]['squares']);
        $this->assertSame(2, $rows[0]['weeks_won']);
    }

    public function test_a_guest_who_registers_mid_claim_still_gets_the_roster_square(): void
    {
        $owner = User::factory()->create();

        $season = app(SeasonService::class)->create([
            'name' => 'Pending Claim Pool',
            'slug' => 'pending-claim-pool',
            'description' => null,
            'total_weeks' => 3,
            'price_per_square' => 500,
            'max_squares_per_user' => 10,
            'number_draw_mode' => Season::DRAW_UPFRONT,
            'is_public' => true,
            'payment_instructions' => null,
        ], $owner);

        $roster = $season->roster();
        $newcomer = User::factory()->create();

        // The grid sends guests to /login carrying the square they picked.
        $this->actingAs($newcomer)
            ->withSession(['pending_claim' => [
                'board_uuid' => $roster->uuid,
                'row' => 6,
                'col' => 7,
            ]])
            ->get('/pending-claim-pool/roster')
            ->assertOk();

        $this->assertSame($newcomer->id, $roster->getSquareAt(6, 7)?->user_id);
    }
}
