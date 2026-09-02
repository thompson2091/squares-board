<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Board;
use App\Models\User;
use App\Models\Winner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Builds the view-model that boards/show.blade.php expects.
 *
 * Shared by BoardController and SeasonController so a season's roster and week
 * pages behave exactly like a standalone board - most importantly the
 * guest -> login -> auto-claim handoff, which would otherwise drop the square
 * someone picked before registering.
 */
trait BuildsBoardView
{
    /**
     * @return array<string, mixed>
     */
    protected function buildBoardView(Request $request, Board $board): array
    {
        $board->loadMissing(['squares.user', 'owner', 'payoutRules', 'winners', 'gameScores']);

        $user = Auth::user();
        $userSquares = [];
        $canClaim = false;
        $isGuest = $user === null;
        $autoClaimMessage = null;
        $autoClaimError = null;

        // Process pending claim from session (after login/registration)
        if ($user !== null && $request->session()->has('pending_claim')) {
            $pendingClaim = $request->session()->pull('pending_claim');

            // Only process if this is the board they intended to claim on
            if ($pendingClaim['board_uuid'] === $board->uuid) {
                $result = $this->processPendingClaim($board, $user, $pendingClaim);

                if ($result['success']) {
                    $autoClaimMessage = $result['message'];
                    // Reload squares to reflect the new claim
                    $board->load(['squares.user']);
                } else {
                    $autoClaimError = $result['message'];
                }
            }
        }

        if ($user !== null) {
            $userSquares = $board->squares
                ->where('user_id', $user->id)
                ->pluck('id')
                ->toArray();
            $canClaim = $board->canUserClaim($user);
        }

        // Organize squares into a 10x10 grid
        $grid = [];
        foreach ($board->squares as $square) {
            $grid[$square->row][$square->col] = $square;
        }

        // Build winning squares map: square_id => [{type, quarter}, ...]
        $winningSquares = [];
        foreach ($board->winners as $winner) {
            $squareId = $winner->square_id;

            if (! isset($winningSquares[$squareId])) {
                $winningSquares[$squareId] = [];
            }

            // Determine winner type from boolean flags
            $type = 'primary';
            if ($winner->is_2mw) {
                $type = '2mw';
            } elseif ($winner->is_touching) {
                $type = 'touching';
            } elseif ($winner->is_reverse) {
                $type = 'reverse';
            }

            $winningSquares[$squareId][] = [
                'type' => $type,
                'quarter' => $winner->quarter,
            ];
        }

        // Calculate payouts by display name for the sidebar leaderboard
        // Group by display name so same user with different square names shows separately
        $payoutsByDisplayName = $board->winners
            ->load(['user', 'square'])
            ->groupBy(function (Winner $winner): string {
                return $winner->square->displayNameForSquare ?? $winner->user->name;
            })
            ->map(function ($winners, $displayName) {
                $firstWinner = $winners->first();

                return [
                    'display_name' => $displayName,
                    'user' => $firstWinner?->user,
                    'total' => $winners->sum('payout_amount'),
                    'wins' => $winners->count(),
                ];
            })
            ->sortByDesc('total');

        return [
            'board' => $board,
            'grid' => $grid,
            'userSquares' => $userSquares,
            'canClaim' => $canClaim,
            'isGuest' => $isGuest,
            'boardIsOpen' => $board->isOpen(),
            'isAdmin' => $user !== null && $board->isAdminUser($user),
            'autoClaimMessage' => $autoClaimMessage,
            'autoClaimError' => $autoClaimError,
            'winningSquares' => $winningSquares,
            'payoutsByDisplayName' => $payoutsByDisplayName,
        ];
    }

    /**
     * Process a pending square claim after user authentication.
     *
     * @param  array{board_uuid: string, row: int, col: int}  $pendingClaim
     * @return array{success: bool, message: string}
     */
    protected function processPendingClaim(Board $board, User $user, array $pendingClaim): array
    {
        $row = $pendingClaim['row'];
        $col = $pendingClaim['col'];

        // Check if board is still open
        if (! $board->isOpen()) {
            return [
                'success' => false,
                'message' => 'This board is no longer open for claiming squares.',
            ];
        }

        // Check if user can claim
        if (! $board->canUserClaim($user)) {
            return [
                'success' => false,
                'message' => sprintf(
                    'You have reached the maximum of %d squares per user.',
                    $board->max_squares_per_user
                ),
            ];
        }

        // Get the square
        $square = $board->getSquareAt($row, $col);

        if ($square === null) {
            return [
                'success' => false,
                'message' => 'Square not found.',
            ];
        }

        // Check if square is already claimed
        if ($square->isClaimed()) {
            return [
                'success' => false,
                'message' => 'Sorry, that square was claimed while you were registering. Please choose another.',
            ];
        }

        // Claim the square
        $square->claim($user);

        return [
            'success' => true,
            'message' => sprintf('Square at row %d, column %d has been claimed for you!', $row + 1, $col + 1),
        ];
    }
}
