<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Square;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SquareController extends Controller
{
    /**
     * Claim a square on a board.
     */
    public function claim(Request $request, Board $board, int $row, int $col): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to claim a square.',
            ], 401);
        }

        // Validate row and col are within bounds
        if ($row < 0 || $row > 9 || $col < 0 || $col > 9) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid square coordinates.',
            ], 400);
        }

        // Check if board is open
        if (! $board->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'This board is not open for claiming squares.',
            ], 403);
        }

        // Check if user can claim more squares
        if (! $board->canUserClaim($user)) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'You have reached the maximum of %d squares per user.',
                    $board->max_squares_per_user
                ),
            ], 403);
        }

        // Get the square
        $square = $board->getSquareAt($row, $col);

        if ($square === null) {
            return response()->json([
                'success' => false,
                'message' => 'Square not found.',
            ], 404);
        }

        // Check if square is already claimed
        if ($square->isClaimed()) {
            return response()->json([
                'success' => false,
                'message' => 'This square has already been claimed.',
            ], 409);
        }

        // Claim the square
        $square->claim($user);

        return response()->json([
            'success' => true,
            'message' => 'Square claimed successfully!',
            'square' => [
                'id' => $square->id,
                'row' => $square->row,
                'col' => $square->col,
                'user_id' => $square->user_id,
                'user_name' => $user->name,
                'claimed_at' => $square->claimed_at?->toIso8601String(),
            ],
            'user_square_count' => $board->userSquareCount($user),
            'can_claim_more' => $board->canUserClaim($user),
        ]);
    }

    /**
     * Release a claimed square.
     */
    public function release(Request $request, Board $board, Square $square): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be logged in to release a square.',
                ], 401);
            }

            return back()->withErrors(['auth' => 'You must be logged in to release a square.']);
        }

        // Verify square belongs to this board
        if ($square->board_id !== $board->id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Square does not belong to this board.',
                ], 400);
            }

            return back()->withErrors(['square' => 'Square does not belong to this board.']);
        }

        // Check authorization
        $canRelease = $square->isClaimedBy($user) || $board->isAdminUser($user);

        if (! $canRelease) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to release this square.',
                ], 403);
            }

            return back()->withErrors(['permission' => 'You do not have permission to release this square.']);
        }

        // Check if board allows releases (only when open or draft)
        if (! $board->isOpen() && ! $board->isDraft()) {
            // Allow admins to release even when locked
            if (! $board->isAdminUser($user)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Squares cannot be released after the board is locked.',
                    ], 403);
                }

                return back()->withErrors(['status' => 'Squares cannot be released after the board is locked.']);
            }
        }

        // Check if square is actually claimed
        if (! $square->isClaimed()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This square is not claimed.',
                ], 400);
            }

            return back()->withErrors(['square' => 'This square is not claimed.']);
        }

        // Release the square
        $square->release();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Square released successfully!',
                'square' => [
                    'id' => $square->id,
                    'row' => $square->row,
                    'col' => $square->col,
                    'user_id' => null,
                ],
                'user_square_count' => $board->userSquareCount($user),
                'can_claim_more' => $board->canUserClaim($user),
            ]);
        }

        return back()->with('success', 'Square released successfully!');
    }
}
