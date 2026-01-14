<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Board;
use App\Models\Square;
use App\Models\User;

class SquarePolicy
{
    /**
     * Determine whether the user can claim a square.
     */
    public function claim(User $user, Square $square): bool
    {
        $board = $square->board;

        // Board must be open
        if (! $board->isOpen()) {
            return false;
        }

        // Square must not be claimed
        if ($square->isClaimed()) {
            return false;
        }

        // User must not have exceeded max squares
        return $board->canUserClaim($user);
    }

    /**
     * Determine whether the user can release a square.
     */
    public function release(User $user, Square $square): bool
    {
        $board = $square->board;

        // Square must be claimed
        if (! $square->isClaimed()) {
            return false;
        }

        // User who claimed it can release (if board is open/draft)
        if ($square->user_id === $user->id) {
            return $board->isOpen() || $board->isDraft();
        }

        // Board owner/admin can always release
        return $board->isAdminUser($user);
    }

    /**
     * Determine whether the user can update payment status of a square.
     */
    public function updatePayment(User $user, Square $square): bool
    {
        $board = $square->board;

        // Only board admins can update payment status
        return $board->isAdminUser($user);
    }
}
