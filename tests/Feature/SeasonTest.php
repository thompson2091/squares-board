<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Board;
use App\Models\PayoutRule;
use App\Models\Season;
use App\Models\User;
use App\Services\SeasonService;
use App\Support\SeasonWeek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonTest extends TestCase
{
    use RefreshDatabase;

    private function makeSeason(User $owner, int $weeks = 4, string $drawMode = Season::DRAW_UPFRONT): Season
    {
        return app(SeasonService::class)->create([
            'name' => 'Sunday Night Squares',
            'slug' => 'sunday-night-squares',
            'description' => null,
            'total_weeks' => $weeks,
            'price_per_square' => 500,
            'max_squares_per_user' => 10,
            'number_draw_mode' => $drawMode,
            'is_public' => true,
            'payment_instructions' => null,
        ], $owner);
    }

    /**
     * Claim the first $count squares on the roster for a user.
     */
    private function claimSquares(Season $season, User $user, int $count): void
    {
        $season->roster()?->squares()
            ->orderBy('row')->orderBy('col')
            ->limit($count)
            ->get()
            ->each(fn ($square) => $square->claim($user));
    }

    private function addPayoutRules(Season $season): void
    {
        $roster = $season->roster();

        foreach ([['Q1', 2000], ['Q2', 2000], ['Q3', 2000], ['final', 4000]] as [$quarter, $amount]) {
            $roster?->payoutRules()->create([
                'quarter' => $quarter,
                'payout_type' => 'percentage',
                'amount' => $amount,
                'winner_type' => 'primary',
            ]);
        }
    }

    public function test_creating_a_season_makes_a_roster_board_with_100_squares(): void
    {
        $owner = User::factory()->create();

        $season = $this->makeSeason($owner);

        $roster = $season->roster();

        $this->assertNotNull($roster);
        $this->assertTrue($roster->isRoster());
        $this->assertSame(Board::STATUS_OPEN, $roster->status);
        $this->assertSame(100, $roster->squares()->count());
        $this->assertSame(Season::STATUS_OPEN, $season->status);

        // Season settings read through the roster board.
        $this->assertSame(500, $season->price_per_square);
        $this->assertSame(2000, $season->season_total);
        $this->assertSame('$20.00', $season->season_total_display);
    }

    public function test_players_claim_roster_squares_through_the_existing_route(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $season = $this->makeSeason($owner);
        $roster = $season->roster();

        $this->actingAs($player)
            ->postJson("/boards/{$roster->uuid}/squares/3/4/claim")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame($player->id, $roster->getSquareAt(3, 4)?->user_id);
    }

    public function test_starting_a_season_does_not_require_a_full_roster(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 4);
        $this->claimSquares($season, $player, 63);

        app(SeasonService::class)->start($season->fresh());

        $season = $season->fresh();

        $this->assertSame(Season::STATUS_ACTIVE, $season->status);
        $this->assertCount(4, $season->weekBoards);
        // Signups close so there are no late joiners to prorate.
        $this->assertSame(Board::STATUS_LOCKED, $season->roster()?->status);
    }

    public function test_each_week_mirrors_the_roster_and_draws_its_own_numbers(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 4);
        $this->claimSquares($season, $player, 63);

        app(SeasonService::class)->start($season->fresh());

        $season = $season->fresh();
        $draws = [];

        foreach ($season->weekBoards as $week) {
            $this->assertSame(100, $week->squares()->count());
            $this->assertSame(63, $week->squares()->whereNotNull('user_id')->count());
            $this->assertSame(Board::STATUS_LOCKED, $week->status);
            $this->assertSame(SeasonWeek::TBD, $week->team_row);

            // Drawn up front, but hidden until the week is revealed.
            $this->assertNotNull($week->row_numbers);
            $this->assertFalse($week->numbers_revealed);

            $draws[] = json_encode([$week->row_numbers, $week->col_numbers]);
        }

        $this->assertCount(4, array_unique($draws), 'Each week should get its own draw.');
    }

    public function test_manual_draw_mode_leaves_numbers_undrawn_until_revealed(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 3, drawMode: Season::DRAW_MANUAL);
        $this->claimSquares($season, $player, 20);

        app(SeasonService::class)->start($season->fresh());

        $season = $season->fresh();
        $week = $season->weekBoard(1);

        $this->assertNull($week?->row_numbers);

        app(SeasonService::class)->revealWeek($week);

        $week->refresh();
        $this->assertNotNull($week->row_numbers);
        $this->assertTrue($week->numbers_revealed);
    }

    public function test_a_week_uses_the_seasons_shared_payout_rules(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 3);
        $this->addPayoutRules($season);
        $this->claimSquares($season, $player, 100);

        app(SeasonService::class)->start($season->fresh());

        $week = $season->fresh()->weekBoard(1);

        // Weekly boards carry no rules of their own.
        $this->assertSame(0, $week->payoutRules()->count());
        $this->assertCount(4, $week->effective_payout_rules);
    }

    public function test_entering_a_weeks_first_score_reveals_its_numbers_and_pays_the_claimed_pot(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 3, drawMode: Season::DRAW_MANUAL);
        $this->addPayoutRules($season);
        $this->claimSquares($season, $player, 100);

        app(SeasonService::class)->start($season->fresh());

        $week = $season->fresh()->weekBoard(1);
        $this->assertFalse($week->numbers_revealed);

        $this->actingAs($owner)
            ->post("/manage/boards/{$week->uuid}/scores", [
                'quarter' => 'Q1',
                'team_row_score' => 7,
                'team_col_score' => 3,
            ])
            ->assertRedirect();

        $week->refresh();

        $this->assertTrue($week->numbers_revealed, 'The week should reveal itself on its first score.');

        // Pot is claimed x price: 100 x $5 = $500, and Q1 pays 20% of it.
        $winner = $week->winners()->where('quarter', 'Q1')->first();
        $this->assertNotNull($winner);
        $this->assertSame(10000, $winner->payout_amount);
    }

    public function test_weeks_can_be_preloaded_while_signups_stay_open(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, 4);

        $created = app(SeasonService::class)->createWeeks($season);

        $this->assertSame(4, $created);
        $season->refresh();

        // Signups carry on: the roster is still open and the season is not active.
        $this->assertSame(Season::STATUS_OPEN, $season->status);
        $this->assertSame(Board::STATUS_OPEN, $season->roster()?->status);
        $this->assertCount(4, $season->weekBoards()->get());

        // The short URL still lands on signups rather than on week 1.
        $this->get('/sunday-night-squares')->assertRedirect($season->rosterUrl());
    }

    public function test_preloaded_matchups_survive_starting_the_season(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, 4, Season::DRAW_MANUAL);
        $this->addPayoutRules($season);

        $service = app(SeasonService::class);
        $service->createWeeks($season);
        $season->load('weekBoards');
        $service->updateWeeks($season, [1 => ['team_row' => 'Jaguars', 'team_col' => 'Browns']]);

        $player = User::factory()->create();
        $this->claimSquares($season, $player, 3);

        $service->start($season->fresh());
        $season->refresh()->load('weekBoards');

        // Weeks are not duplicated, and the schedule is untouched.
        $this->assertCount(4, $season->weekBoards);
        $this->assertSame('Browns', $season->weekBoard(1)?->team_col);
        $this->assertSame(Season::STATUS_ACTIVE, $season->status);
        $this->assertSame(Board::STATUS_LOCKED, $season->roster()?->status);

        // The roster reached every preloaded week.
        $this->assertSame(3, $season->weekBoard(1)?->squares()->where('user_id', $player->id)->count());
    }

    public function test_starting_an_already_active_season_is_refused(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, 3);
        $this->addPayoutRules($season);

        app(SeasonService::class)->start($season);

        $this->expectException(\RuntimeException::class);
        app(SeasonService::class)->start($season->fresh());
    }

    public function test_claims_during_signups_reach_preloaded_weeks(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, 3);
        app(SeasonService::class)->createWeeks($season);

        $player = User::factory()->create();
        $this->claimSquares($season, $player, 2);

        $season->refresh()->load('weekBoards');
        $this->assertSame(2, $season->weekBoard(2)?->squares()->where('user_id', $player->id)->count());
    }

    public function test_an_admin_gets_a_manage_link_on_the_season_pages(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, 3);

        $manageUrl = route('manage.seasons.index', $season);

        // Guest first: actingAs would otherwise carry into the next request.
        $this->get($season->rosterUrl())->assertDontSee($manageUrl, false);
        $this->actingAs($owner)->get($season->rosterUrl())->assertSee($manageUrl, false);
    }

    public function test_the_roster_prints_without_a_matchup_or_numbers(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, 3);
        $roster = $season->roster();

        $response = $this->get(route('boards.print', $roster));

        $response->assertOk()
            ->assertSee('Season roster', false)
            ->assertSee('Weekly Pot', false)
            // The roster's placeholder team names never reach the page.
            ->assertDontSee('Season vs Roster', false);
    }

    public function test_season_urls_resolve_by_slug(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 3);
        $this->claimSquares($season, $player, 10);

        $this->get('/sunday-night-squares/roster')->assertOk();

        // Before the season starts, the short URL sends people to signups.
        $this->get('/sunday-night-squares')->assertRedirect($season->rosterUrl());

        app(SeasonService::class)->start($season->fresh());

        $this->get('/sunday-night-squares')->assertOk();
        $this->get('/sunday-night-squares/week/2')->assertOk();
        $this->get('/sunday-night-squares/standings')->assertOk();
        $this->get('/sunday-night-squares/week/99')->assertNotFound();
    }

    public function test_the_short_url_lands_on_the_nearest_upcoming_week(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 4);
        $this->claimSquares($season, $player, 10);

        app(SeasonService::class)->start($season->fresh());

        $season = $season->fresh();
        $season->weekBoard(1)?->update(['game_date' => now()->subWeeks(2)]);
        $season->weekBoard(2)?->update(['game_date' => now()->subDay()]);
        $season->weekBoard(3)?->update(['game_date' => now()->addDay()]);
        $season->weekBoard(4)?->update(['game_date' => now()->addWeek()]);

        $this->assertSame(3, $season->fresh()->currentWeekNumber());
    }

    public function test_the_short_url_moves_on_when_later_weeks_have_no_kickoff_yet(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 4);
        $this->claimSquares($season, $owner, 10);

        app(SeasonService::class)->start($season->fresh());

        $season = $season->fresh();

        // Only the first two weeks have been scheduled so far, and both are past.
        $season->weekBoard(1)?->update(['game_date' => now()->subWeeks(3)]);
        $season->weekBoard(2)?->update(['game_date' => now()->subWeeks(2)]);

        // Week 3 is undated but unplayed, so it - not the finished week 2 - is
        // where the season URL should land.
        $this->assertSame(3, $season->fresh()->currentWeekNumber());
    }

    public function test_a_public_season_is_discoverable_on_the_browse_page(): void
    {
        $owner = User::factory()->create();
        $this->makeSeason($owner);

        $this->get('/boards/browse')
            ->assertOk()
            ->assertSee('Sunday Night Squares');
    }

    public function test_a_private_season_stays_off_the_browse_page(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner);
        $season->roster()?->update(['is_public' => false]);

        $this->get('/boards/browse')
            ->assertOk()
            ->assertDontSee('Sunday Night Squares');
    }

    public function test_a_season_completes_when_its_last_week_does(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 2);
        $this->addPayoutRules($season);
        $this->claimSquares($season, $owner, 100);

        app(SeasonService::class)->start($season->fresh());
        $season = $season->fresh();

        foreach ([1, 2] as $number) {
            $week = $season->weekBoard($number);

            foreach (['Q1', 'Q2', 'Q3', 'final'] as $quarter) {
                $this->actingAs($owner)->post("/manage/boards/{$week->uuid}/scores", [
                    'quarter' => $quarter,
                    'team_row_score' => 7,
                    'team_col_score' => 3,
                ])->assertRedirect();
            }

            $this->actingAs($owner)
                ->post("/manage/boards/{$week->uuid}/scores/complete")
                ->assertRedirect();

            // Only once the final week is done does the season finish.
            $expected = $number === 2 ? Season::STATUS_COMPLETED : Season::STATUS_ACTIVE;
            $this->assertSame($expected, $season->fresh()->status);
        }
    }

    public function test_week_boards_stay_out_of_the_board_listings(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 4);
        $this->claimSquares($season, $owner, 5);

        app(SeasonService::class)->start($season->fresh());

        $response = $this->actingAs($owner)->get('/boards');

        $response->assertOk();
        $response->assertDontSee('Week 2', escape: false);
        // But the season itself is listed, so players can find it again.
        $response->assertSee('Sunday Night Squares');

        $this->actingAs($owner)->get('/dashboard')->assertOk()->assertSee('Sunday Night Squares');
    }

    public function test_a_slug_cannot_collide_with_a_board_a_season_or_a_reserved_path(): void
    {
        $owner = User::factory()->create();
        $this->makeSeason($owner);

        // Taken by the season above.
        $this->actingAs($owner)
            ->post('/boards', [
                'name' => 'Clashing Board',
                'slug' => 'sunday-night-squares',
                'team_row' => 'A',
                'team_col' => 'B',
                'price_per_square' => '10.00',
                'max_squares_per_user' => 4,
            ])
            ->assertSessionHasErrors('slug');

        // Reserved first path segment.
        $this->actingAs($owner)
            ->post('/boards', [
                'name' => 'Reserved Board',
                'slug' => 'dashboard',
                'team_row' => 'A',
                'team_col' => 'B',
                'price_per_square' => '10.00',
                'max_squares_per_user' => 4,
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_payout_rules_on_a_week_redirect_to_the_seasons_own_screen(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 3);
        $this->claimSquares($season, $owner, 5);

        app(SeasonService::class)->start($season->fresh());

        $season = $season->fresh();
        $week = $season->weekBoard(1);

        $this->actingAs($owner)
            ->get("/manage/boards/{$week->uuid}/payouts")
            ->assertRedirect(route('manage.boards.payouts.index', $season->roster()));
    }

    public function test_deleting_a_season_removes_its_boards(): void
    {
        $owner = User::factory()->create();
        $season = $this->makeSeason($owner, weeks: 3);
        $this->claimSquares($season, $owner, 5);

        app(SeasonService::class)->start($season->fresh());

        $this->actingAs($owner)
            ->delete(route('seasons.destroy', $season))
            ->assertRedirect(route('boards.index'));

        $this->assertDatabaseMissing('seasons', ['id' => $season->id]);
        $this->assertSame(0, Board::where('season_id', $season->id)->count());
        $this->assertSame(0, PayoutRule::count());
    }
}
