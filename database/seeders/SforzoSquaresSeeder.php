<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Season;
use App\Models\User;
use App\Models\Winner;
use App\Services\SeasonService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Sforzo Squares - Michelle's 2026 Browns season pool.
 *
 * Safe to re-run: the season is matched on its slug, and every step below
 * either creates what is missing or leaves what is already there alone. It
 * refuses to touch a season that has already started, so a re-run can never
 * rewrite a week that has been played.
 */
class SforzoSquaresSeeder extends Seeder
{
    private const SLUG = 'sforzo';

    private const OWNER_ID = 17;

    private const TOTAL_WEEKS = 18;

    /** Cents per square, per week. 18 x $5.00 = $90.00 for the season. */
    private const PRICE_PER_SQUARE = 500;

    /** The Browns sit on the columns every week, so the axis never moves. */
    private const HOME_TEAM = 'Cleveland Browns';

    /**
     * Fixed weekly payouts, in cents. $495 of a full $500 weekly pot.
     *
     * @var array<string, int>
     */
    private const PAYOUTS = [
        'Q1' => 12000,
        'Q2' => 12500,
        'Q3' => 12500,
        'final' => 12500,
    ];

    /**
     * The 2026 Browns schedule. Week 11 is the bye - it stays TBD so Michelle
     * can pick a game for it later. Week 18 has a matchup but no kickoff yet.
     *
     * @var array<int, array{opponent: string, kickoff: string|null}>
     */
    private const SCHEDULE = [
        1 => ['opponent' => 'Jacksonville Jaguars', 'kickoff' => '2026-09-13 13:00:00'],
        2 => ['opponent' => 'Tampa Bay Buccaneers', 'kickoff' => '2026-09-20 13:00:00'],
        3 => ['opponent' => 'Carolina Panthers', 'kickoff' => '2026-09-27 13:00:00'],
        4 => ['opponent' => 'Pittsburgh Steelers', 'kickoff' => '2026-10-01 20:15:00'],
        5 => ['opponent' => 'New York Jets', 'kickoff' => '2026-10-11 13:00:00'],
        6 => ['opponent' => 'Baltimore Ravens', 'kickoff' => '2026-10-18 13:00:00'],
        7 => ['opponent' => 'Tennessee Titans', 'kickoff' => '2026-10-25 13:00:00'],
        8 => ['opponent' => 'Pittsburgh Steelers', 'kickoff' => '2026-11-01 13:00:00'],
        9 => ['opponent' => 'New Orleans Saints', 'kickoff' => '2026-11-08 13:00:00'],
        10 => ['opponent' => 'Houston Texans', 'kickoff' => '2026-11-15 13:00:00'],
        // 11 => bye week, left TBD on purpose.
        12 => ['opponent' => 'Las Vegas Raiders', 'kickoff' => '2026-11-29 13:00:00'],
        13 => ['opponent' => 'Cincinnati Bengals', 'kickoff' => '2026-12-06 13:00:00'],
        14 => ['opponent' => 'Atlanta Falcons', 'kickoff' => '2026-12-13 13:00:00'],
        15 => ['opponent' => 'New York Giants', 'kickoff' => '2026-12-20 13:00:00'],
        16 => ['opponent' => 'Baltimore Ravens', 'kickoff' => '2026-12-27 13:00:00'],
        17 => ['opponent' => 'Indianapolis Colts', 'kickoff' => '2027-01-03 13:00:00'],
        18 => ['opponent' => 'Cincinnati Bengals', 'kickoff' => null],
    ];

    private const DESCRIPTION = <<<'TEXT'
        $5.00 per square (x 18 weeks = $90.00 total per square).

        You can buy as many squares as you want and you keep the same square all year. Your square(s) is/are not guaranteed until your payment is verified. When confirmed, your square(s) will be changed to GREEN.

        Numbers will be randomly drawn and assigned after all squares are taken. New numbers are drawn every week.
        TEXT;

    private const PAYMENT_INSTRUCTIONS = <<<'TEXT'
        Your square(s) are not guaranteed until your payment is verified. Once Michelle confirms your payment, your square(s) will turn GREEN on the roster.

        $90.00 per square covers all 18 weeks.
        TEXT;

    public function run(): void
    {
        $owner = User::find(self::OWNER_ID);

        if ($owner === null) {
            throw new RuntimeException('Owner user #'.self::OWNER_ID.' not found.');
        }

        $season = Season::where('slug', self::SLUG)->first();

        if ($season !== null && ($season->isActive() || $season->isCompleted())) {
            throw new RuntimeException('Sforzo Squares has already started - refusing to touch a running season.');
        }

        $season ??= $this->createSeason($owner);

        $this->ensurePayoutRules($season);
        $this->ensureWeeks($season);
        $this->ensureSchedule($season);

        $this->command?->info('Sforzo Squares ready at /'.self::SLUG);
    }

    private function createSeason(User $owner): Season
    {
        return app(SeasonService::class)->create([
            'name' => 'Sforzo Squares',
            'slug' => self::SLUG,
            'description' => self::DESCRIPTION,
            'total_weeks' => self::TOTAL_WEEKS,
            'price_per_square' => self::PRICE_PER_SQUARE,
            // "Buy as many squares as you want" - the whole grid is the cap.
            'max_squares_per_user' => 100,
            // Numbers are drawn once all the squares are taken, a week at a time.
            'number_draw_mode' => Season::DRAW_MANUAL,
            // Link-only: the pool is shared as /sforzo, not browsed.
            'is_public' => false,
            'payment_instructions' => self::PAYMENT_INSTRUCTIONS,
        ], $owner);
    }

    /**
     * Fixed weekly payouts on the roster board, shared by every week.
     */
    private function ensurePayoutRules(Season $season): void
    {
        $roster = $season->roster();

        if ($roster === null) {
            throw new RuntimeException('Season has no roster board.');
        }

        foreach (self::PAYOUTS as $quarter => $amount) {
            $roster->payoutRules()->updateOrCreate(
                ['quarter' => $quarter, 'winner_type' => 'primary'],
                ['payout_type' => 'fixed', 'amount' => $amount],
            );
        }
    }

    /**
     * The 18 weekly boards. Signups stay open - the schedule just needs
     * somewhere to live before anyone claims a square.
     */
    private function ensureWeeks(Season $season): void
    {
        app(SeasonService::class)->createWeeks($season);
        $season->load('weekBoards');
    }

    /**
     * Fill in the matchups. A week that has already been played keeps what it
     * has, and the bye week is left TBD for Michelle to choose.
     */
    private function ensureSchedule(Season $season): void
    {
        $playedIds = Winner::whereIn('board_id', $season->weekBoards->pluck('id'))
            ->distinct()
            ->pluck('board_id')
            ->all();

        $weeks = [];

        foreach (self::SCHEDULE as $number => $game) {
            $board = $season->weekBoard($number);

            if ($board === null || in_array($board->id, $playedIds, true)) {
                continue;
            }

            $weeks[$number] = [
                // Opponent on the rows, Browns on the columns, every week.
                'team_row' => $game['opponent'],
                'team_col' => self::HOME_TEAM,
                'game_date' => $game['kickoff'],
            ];
        }

        app(SeasonService::class)->updateWeeks($season, $weeks);
    }

    /**
     * What a full grid pays out each week, for the summary line below.
     */
    public static function weeklyPayoutTotal(): int
    {
        return array_sum(self::PAYOUTS);
    }
}
