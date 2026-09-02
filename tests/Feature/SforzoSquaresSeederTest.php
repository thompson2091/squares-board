<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Season;
use App\Models\User;
use Database\Seeders\SforzoSquaresSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The seeder writes to a live database, so it gets checked here first.
 */
class SforzoSquaresSeederTest extends TestCase
{
    use RefreshDatabase;

    private function runSeeder(): Season
    {
        // The seeder resolves its owner by id, so pin one down.
        User::factory()->create(['id' => 17, 'name' => 'Michelle Sforzo']);

        $this->seed(SforzoSquaresSeeder::class);

        $season = Season::where('slug', 'sforzo')->firstOrFail();

        return $season->load('rosterBoard.payoutRules', 'weekBoards');
    }

    public function test_it_builds_the_pool_with_signups_open(): void
    {
        $season = $this->runSeeder();

        $this->assertSame('Sforzo Squares', $season->name);
        $this->assertSame(17, $season->owner_id);
        $this->assertSame(18, $season->total_weeks);
        $this->assertSame(Season::DRAW_MANUAL, $season->number_draw_mode);
        $this->assertSame(Season::STATUS_OPEN, $season->status);

        // $5.00 a week, $90.00 for the season, and anyone can buy any number.
        $this->assertSame(500, $season->price_per_square);
        $this->assertSame('$90.00', $season->season_total_display);
        $this->assertSame(100, $season->max_squares_per_user);

        // Unlisted, and signups are open on the roster.
        $this->assertFalse($season->is_public);
        $this->assertSame(Board::STATUS_OPEN, $season->roster()?->status);
        $this->assertSame(100, $season->roster()?->squares()->count());
    }

    public function test_it_loads_the_browns_schedule_with_the_bye_left_open(): void
    {
        $season = $this->runSeeder();

        $this->assertCount(18, $season->weekBoards);

        // Browns on the columns every week, opponent on the rows.
        $this->assertSame('Cleveland Browns', $season->weekBoard(1)?->team_col);
        $this->assertSame('Jacksonville Jaguars', $season->weekBoard(1)?->team_row);
        $this->assertSame('2026-09-13 13:00', $season->weekBoard(1)?->game_date?->format('Y-m-d H:i'));

        // Thursday night game against the Steelers.
        $this->assertSame('2026-10-01 20:15', $season->weekBoard(4)?->game_date?->format('Y-m-d H:i'));

        // Week 17 rolls into the new year.
        $this->assertSame('2027-01-03 13:00', $season->weekBoard(17)?->game_date?->format('Y-m-d H:i'));

        // The bye week is Michelle's to fill in later.
        $this->assertFalse($season->week(11)?->has_matchup);
        $this->assertNull($season->weekBoard(11)?->game_date);

        // Week 18 has a matchup but no kickoff time yet.
        $this->assertTrue($season->week(18)?->has_matchup);
        $this->assertNull($season->weekBoard(18)?->game_date);

        // Nothing is drawn yet - manual draw, once the squares are sold.
        $this->assertNull($season->weekBoard(1)?->row_numbers);
        $this->assertFalse($season->weekBoard(1)?->numbers_revealed);
    }

    public function test_it_sets_the_fixed_weekly_payouts(): void
    {
        $season = $this->runSeeder();
        $rules = $season->payoutRules;

        $this->assertCount(4, $rules);
        $this->assertTrue($rules->every(fn ($rule): bool => $rule->payout_type === 'fixed'));
        $this->assertSame(12000, $rules->firstWhere('quarter', 'Q1')?->amount);
        $this->assertSame(12500, $rules->firstWhere('quarter', 'final')?->amount);

        // $495.00 of a full $500.00 weekly pot.
        $this->assertSame(49500, SforzoSquaresSeeder::weeklyPayoutTotal());
    }

    public function test_the_short_url_lands_on_signups(): void
    {
        $this->runSeeder();

        $this->get('/sforzo')->assertRedirect(url('/sforzo/roster'));
        $this->get('/sforzo/roster')->assertOk()->assertSee('Sforzo Squares');
        $this->get('/sforzo/week/1')->assertOk()->assertSee('Cleveland Browns');
        $this->get('/sforzo/standings')->assertOk();
    }

    public function test_running_it_twice_changes_nothing(): void
    {
        $season = $this->runSeeder();

        $this->seed(SforzoSquaresSeeder::class);

        $this->assertSame(1, Season::where('slug', 'sforzo')->count());
        $this->assertSame(18, $season->weekBoards()->count());
        $this->assertSame(4, $season->roster()?->payoutRules()->count());
        // 1 roster + 18 weeks, each with its own 100 squares.
        $this->assertSame(19, Board::where('season_id', $season->id)->count());
    }
}
