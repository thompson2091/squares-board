<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Board;
use App\Models\BoardAdmin;
use App\Models\User;

class BoardPolicy
{
    /**
     * Determine whether the user can view any boards.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the board.
     *
     * Both public and private boards can be viewed by anyone with the link.
     * The only difference is discoverability:
     * - Public boards appear in browse/search
     * - Private boards are "unlisted" (link-only access)
     */
    public function view(?User $user, Board $board): bool
    {
        // Anyone with the link can view any board
        return true;
    }

    /**
     * Determine whether the user can create boards.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the board.
     */
    public function update(User $user, Board $board): bool
    {
        // Platform admins can update any board
        if ($user->isPlatformAdmin()) {
            return true;
        }

        // Owner can update
        if ($board->owner_id === $user->id) {
            return true;
        }

        // Board admins (not viewers) can update
        $adminEntry = BoardAdmin::where('board_id', $board->id)
            ->where('user_id', $user->id)
            ->first();

        if ($adminEntry !== null && $adminEntry->role === BoardAdmin::ROLE_ADMIN) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage the board (payments, scores, etc).
     */
    public function manage(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    /**
     * Determine whether the user can delete the board.
     */
    public function delete(User $user, Board $board): bool
    {
        // Only owner can delete
        return $board->owner_id === $user->id;
    }

    /**
     * Determine whether the user can manage admins for the board.
     */
    public function manageAdmins(User $user, Board $board): bool
    {
        // Only owner can manage admins
        return $board->owner_id === $user->id;
    }

    /**
     * Determine whether the user can lock the board.
     */
    public function lock(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    /**
     * Determine whether the user can manage payouts for the board.
     */
    public function managePayouts(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    /**
     * Determine whether the user can manage payment status for squares.
     */
    public function managePayments(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    /**
     * Determine whether the user can record scores for the board.
     */
    public function recordScores(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }
}
