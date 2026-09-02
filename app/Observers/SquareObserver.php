<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Square;
use App\Services\SeasonService;

/**
 * Keeps a season's weekly boards in step with its roster.
 *
 * Everything that mutates a square goes through an Eloquent save - claiming
 * (Square::claim), releasing (Square::release), renaming, and PaymentController's
 * bulk operations, which update each model individually rather than by mass
 * query. Observing the model therefore covers all of them without touching those
 * controllers.
 *
 * No recursion risk: syncRoster only ever writes squares on non-roster boards.
 */
class SquareObserver
{
    public function __construct(private readonly SeasonService $seasons) {}

    public function saved(Square $square): void
    {
        $this->sync($square);
    }

    public function deleted(Square $square): void
    {
        $this->sync($square);
    }

    private function sync(Square $square): void
    {
        $board = $square->board;

        if (! $board->isRoster()) {
            return;
        }

        $season = $board->season;

        // Open seasons sync too: an organizer can load the schedule before
        // signups close, and those weeks should mirror claims as they come in
        // rather than waiting for the season to start.
        if ($season === null || ! ($season->isActive() || $season->isOpen())) {
            return;
        }

        $this->seasons->syncRoster($season, $square->row, $square->col);
    }
}
