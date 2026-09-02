<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Season;
use App\Models\User;

/**
 * Season permissions delegate to the roster board, so co-admins added through
 * BoardAdminController govern the whole season.
 */
class SeasonPolicy
{
    public function __construct(private readonly BoardPolicy $boardPolicy) {}

    /**
     * Determine whether the user can view any seasons.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Anyone with the link can view a season; public only affects discoverability.
     */
    public function view(?User $user, Season $season): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create seasons.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the season.
     */
    public function update(User $user, Season $season): bool
    {
        $roster = $season->rosterBoard;

        if ($roster === null) {
            return $season->owner_id === $user->id || $user->isPlatformAdmin();
        }

        return $this->boardPolicy->update($user, $roster);
    }

    /**
     * Determine whether the user can manage the season (weeks, scores, payouts).
     */
    public function manage(User $user, Season $season): bool
    {
        return $this->update($user, $season);
    }

    /**
     * Determine whether the user can start the season.
     */
    public function start(User $user, Season $season): bool
    {
        return $this->update($user, $season);
    }

    /**
     * Only the owner can delete a season, which cascades to all of its boards.
     */
    public function delete(User $user, Season $season): bool
    {
        return $season->owner_id === $user->id;
    }

    /**
     * Only the owner can manage co-admins.
     */
    public function manageAdmins(User $user, Season $season): bool
    {
        return $season->owner_id === $user->id;
    }
}
