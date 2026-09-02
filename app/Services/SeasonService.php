<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Board;
use App\Models\Season;
use App\Models\Square;
use App\Models\User;
use App\Models\Winner;
use App\Support\SeasonWeek;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates and runs season pools.
 *
 * A season is a roster board (who owns which square, for the whole season) plus
 * one board per week. Weekly boards project the roster's ownership and carry
 * their own numbers, matchup, scores and winners.
 */
class SeasonService
{
    /**
     * Create a season and its roster board.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $owner): Season
    {
        return DB::transaction(function () use ($data, $owner): Season {
            $season = Season::create([
                'owner_id' => $owner->id,
                'slug' => $data['slug'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'total_weeks' => (int) $data['total_weeks'],
                'number_draw_mode' => $data['number_draw_mode'] ?? Season::DRAW_UPFRONT,
                'status' => Season::STATUS_OPEN,
            ]);

            $roster = Board::create([
                'owner_id' => $owner->id,
                'season_id' => $season->id,
                'is_roster' => true,
                'week_number' => null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                // The roster has no matchup; its grid is rendered without team labels.
                'team_row' => 'Season',
                'team_col' => 'Roster',
                'price_per_square' => (int) $data['price_per_square'],
                'max_squares_per_user' => (int) $data['max_squares_per_user'],
                'is_public' => (bool) ($data['is_public'] ?? false),
                'status' => Board::STATUS_OPEN,
                'payment_instructions' => $data['payment_instructions'] ?? null,
            ]);

            $roster->seedSquares();

            $season->setRelation('rosterBoard', $roster);

            return $season;
        });
    }

    /**
     * Create the weekly boards without starting the season.
     *
     * Split out of start() so an organizer can load the schedule while signups
     * are still open - matchups have nowhere to live until the weeks exist, and
     * a season is usually announced with its schedule already known.
     *
     * Weeks that already exist are left alone, so this is safe to re-run.
     *
     * @return int the number of weeks created
     */
    public function createWeeks(Season $season): int
    {
        $roster = $season->rosterBoard;

        if ($roster === null) {
            throw new RuntimeException('Season has no roster board.');
        }

        $existing = $season->weekBoards()->pluck('week_number')
            ->map(fn ($number): int => (int) $number)
            ->all();

        $created = 0;

        DB::transaction(function () use ($season, $roster, $existing, &$created): void {
            for ($number = 1; $number <= $season->total_weeks; $number++) {
                if (in_array($number, $existing, true)) {
                    continue;
                }

                $week = Board::create([
                    'owner_id' => $season->owner_id,
                    'season_id' => $season->id,
                    'is_roster' => false,
                    'week_number' => $number,
                    'name' => $season->name.' — Week '.$number,
                    // Matchups get filled in later on the season manage page.
                    'team_row' => SeasonWeek::TBD,
                    'team_col' => SeasonWeek::TBD,
                    'price_per_square' => $roster->price_per_square,
                    'max_squares_per_user' => $roster->max_squares_per_user,
                    // Weeks are never browsable on their own - the season is.
                    'is_public' => false,
                    // Locked from birth: the roster governs who owns what.
                    'status' => Board::STATUS_LOCKED,
                    'numbers_revealed' => false,
                    'payment_instructions' => $roster->payment_instructions,
                ]);

                $week->seedSquares();
                $created++;
            }
        });

        if ($created > 0) {
            $season->load('weekBoards');
        }

        return $created;
    }

    /**
     * Start the season: create any missing weekly boards and close signups.
     *
     * Deliberately does NOT require a full roster. Unlike
     * BoardController::generateNumbers(), an incomplete roster is a warning in
     * the UI, not a block - unclaimed squares simply never win, and the weekly
     * pot is claimed x price.
     *
     * "Started" is a question about status rather than about whether weeks
     * exist, because createWeeks() may already have built them so the schedule
     * could be filled in during signups.
     */
    public function start(Season $season): void
    {
        $roster = $season->rosterBoard;

        if ($roster === null) {
            throw new RuntimeException('Season has no roster board.');
        }

        if ($season->isActive() || $season->isCompleted()) {
            throw new RuntimeException('Season has already been started.');
        }

        DB::transaction(function () use ($season, $roster): void {
            $this->createWeeks($season);

            if ($season->drawsUpfront()) {
                // Drawn now, but stay hidden until the week is revealed.
                foreach ($season->weekBoards as $week) {
                    if ($week->row_numbers === null || $week->col_numbers === null) {
                        $week->generateNumbers();
                    }
                }
            }

            $season->load('weekBoards');

            $this->syncRoster($season);

            // Signups close when the season starts - no late joiners, so there
            // is no proration to work out.
            $roster->update(['status' => Board::STATUS_LOCKED]);
            $season->update(['status' => Season::STATUS_ACTIVE]);
        });
    }

    /**
     * Project the roster's square ownership onto the weekly boards.
     *
     * Skips any week that is already finished or has winners recorded, so a
     * later roster edit can never rewrite a played week's history.
     *
     * @param  int|null  $row  limit to a single square when given
     */
    public function syncRoster(Season $season, ?int $row = null, ?int $col = null): void
    {
        $roster = $season->roster();

        if ($roster === null) {
            return;
        }

        $targetBoardIds = $this->syncableWeekBoardIds($season);

        if ($targetBoardIds === []) {
            return;
        }

        $rosterSquares = $roster->squares()
            ->when($row !== null, fn ($query) => $query->where('row', $row))
            ->when($col !== null, fn ($query) => $query->where('col', $col))
            ->get();

        $now = now();

        // One bulk UPDATE per roster square across every syncable week, rather
        // than loading and saving each week's square individually. A single
        // square sync costs four queries instead of dozens - which matters,
        // because the observer fires this once per square during bulk payment
        // marking. Mass updates skip model events, which is fine here: nothing
        // observes squares on non-roster boards.
        foreach ($rosterSquares as $source) {
            Square::whereIn('board_id', $targetBoardIds)
                ->where('row', $source->row)
                ->where('col', $source->col)
                ->update([
                    'user_id' => $source->user_id,
                    'display_name' => $source->display_name,
                    'is_paid' => $source->is_paid,
                    'claimed_at' => $source->claimed_at,
                    'paid_at' => $source->paid_at,
                    'updated_at' => $now,
                ]);
        }
    }

    /**
     * IDs of the weeks that may still be rewritten from the roster.
     *
     * A week that is finished or already has winners keeps its history.
     *
     * @return list<int>
     */
    private function syncableWeekBoardIds(Season $season): array
    {
        $weekIds = $season->weekBoards()
            ->where('status', '!=', Board::STATUS_COMPLETED)
            ->pluck('id');

        if ($weekIds->isEmpty()) {
            return [];
        }

        $playedIds = Winner::whereIn('board_id', $weekIds)
            ->distinct()
            ->pluck('board_id');

        /** @var list<int> $ids */
        $ids = $weekIds->diff($playedIds)->values()->all();

        return $ids;
    }

    /**
     * Reveal a week's numbers, drawing them first if they don't exist yet.
     */
    public function revealWeek(Board $week): void
    {
        if ($week->row_numbers === null || $week->col_numbers === null) {
            $week->generateNumbers();
        }

        if (! $week->numbers_revealed) {
            $week->update(['numbers_revealed' => true]);
        }
    }

    /**
     * Save the matchups and kickoff times from the manage page's bulk table.
     *
     * @param  array<int, array{team_row?: string|null, team_col?: string|null, game_date?: string|null}>  $weeks
     * @return int the number of weeks changed
     */
    public function updateWeeks(Season $season, array $weeks): int
    {
        $changed = 0;

        DB::transaction(function () use ($season, $weeks, &$changed): void {
            foreach ($season->weekBoards as $week) {
                $number = $week->week_number;
                $input = $number === null ? null : ($weeks[$number] ?? null);

                if ($input === null) {
                    continue;
                }

                $teamRow = trim((string) ($input['team_row'] ?? ''));
                $teamCol = trim((string) ($input['team_col'] ?? ''));
                $gameDate = trim((string) ($input['game_date'] ?? ''));

                $week->fill([
                    'team_row' => $teamRow !== '' ? $teamRow : SeasonWeek::TBD,
                    'team_col' => $teamCol !== '' ? $teamCol : SeasonWeek::TBD,
                    'game_date' => $gameDate !== '' ? $gameDate : null,
                ]);

                if ($week->isDirty()) {
                    $week->save();
                    $changed++;
                }
            }
        });

        return $changed;
    }
}
