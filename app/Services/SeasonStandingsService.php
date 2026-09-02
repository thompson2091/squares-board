<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Season;
use App\Models\Square;
use App\Models\User;
use App\Models\Winner;
use Illuminate\Support\Collection;

/**
 * Adds a season's weeks together into one table.
 *
 * Grouped by square display name rather than by user, matching how the weekly
 * winners sidebar already groups (BoardController::show), so one person's
 * differently-named squares stand on their own.
 */
class SeasonStandingsService
{
    /**
     * @return Collection<int, array{display_name: string, user: User|null, squares: int, paid_squares: int, wins: int, weeks_won: int, total: int}>
     */
    public function forSeason(Season $season): Collection
    {
        /** @var array<string, array{display_name: string, user: User|null, squares: int, paid_squares: int, wins: int, weeks: array<int, true>, total: int}> $rows */
        $rows = [];

        $roster = $season->rosterBoard;

        if ($roster !== null) {
            $claimed = $roster->squares()->with('user')->whereNotNull('user_id')->get();

            foreach ($claimed as $square) {
                $key = $this->keyFor($square);
                $rows[$key] ??= $this->emptyRow($key, $square->user);
                $rows[$key]['squares']++;

                if ($square->is_paid) {
                    $rows[$key]['paid_squares']++;
                }
            }
        }

        $weekBoardIds = $season->weekBoards->pluck('id')->all();

        if ($weekBoardIds !== []) {
            /** @var Collection<int, Winner> $winners */
            $winners = Winner::whereIn('board_id', $weekBoardIds)
                ->with(['square.user', 'board:id,week_number'])
                ->get();

            foreach ($winners as $winner) {
                $square = $winner->square;
                $key = $this->keyFor($square);
                $rows[$key] ??= $this->emptyRow($key, $square->user);
                $rows[$key]['wins']++;
                $rows[$key]['weeks'][(int) $winner->board->week_number] = true;
                $rows[$key]['total'] += $winner->payout_amount;
            }
        }

        return collect($rows)
            ->map(fn (array $row): array => [
                'display_name' => $row['display_name'],
                'user' => $row['user'],
                'squares' => $row['squares'],
                'paid_squares' => $row['paid_squares'],
                'wins' => $row['wins'],
                'weeks_won' => count($row['weeks']),
                'total' => $row['total'],
            ])
            ->sortByDesc(fn (array $row): array => [$row['total'], $row['weeks_won'], $row['squares']])
            ->values();
    }

    /**
     * Total paid out across every played week of the season, in cents.
     */
    public function paidOut(Season $season): int
    {
        $weekBoardIds = $season->weekBoards->pluck('id')->all();

        if ($weekBoardIds === []) {
            return 0;
        }

        return (int) Winner::whereIn('board_id', $weekBoardIds)->sum('payout_amount');
    }

    /**
     * What one user has won across the whole season, in cents.
     */
    public function winningsFor(Season $season, ?User $user): int
    {
        if ($user === null) {
            return 0;
        }

        $weekBoardIds = $season->weekBoards->pluck('id')->all();

        if ($weekBoardIds === []) {
            return 0;
        }

        return (int) Winner::whereIn('board_id', $weekBoardIds)
            ->where('user_id', $user->id)
            ->sum('payout_amount');
    }

    private function keyFor(Square $square): string
    {
        return $square->displayNameForSquare ?? 'Unclaimed';
    }

    /**
     * @return array{display_name: string, user: User|null, squares: int, paid_squares: int, wins: int, weeks: array<int, true>, total: int}
     */
    private function emptyRow(string $displayName, ?User $user): array
    {
        return [
            'display_name' => $displayName,
            'user' => $user,
            'squares' => 0,
            'paid_squares' => 0,
            'wins' => 0,
            'weeks' => [],
            'total' => 0,
        ];
    }
}
