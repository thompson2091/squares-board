<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Square;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Display the payment tracking page.
     */
    public function index(Board $board): View
    {
        $this->authorize('manage', $board);

        $squares = $board->squares()
            ->with('user')
            ->whereNotNull('user_id')
            ->orderBy('row')
            ->orderBy('col')
            ->get();

        // Calculate payment statistics
        $totalSquares = $squares->count();
        $paidSquares = $squares->where('is_paid', true)->count();
        $unpaidSquares = $totalSquares - $paidSquares;

        /** @var int $pricePerSquare */
        $pricePerSquare = $board->price_per_square ?? 0;

        $totalExpected = $totalSquares * $pricePerSquare;
        $totalCollected = $paidSquares * $pricePerSquare;
        $totalOutstanding = $unpaidSquares * $pricePerSquare;

        // Group squares by user for easier viewing
        $squaresByUser = $squares->groupBy('user_id')
            ->map(function ($userSquares) use ($pricePerSquare) {
                /** @var \Illuminate\Support\Collection<int, Square> $userSquares */
                $firstSquare = $userSquares->first();
                $totalOwed = $userSquares->count() * $pricePerSquare;
                $totalPaid = $userSquares->where('is_paid', true)->count() * $pricePerSquare;

                return [
                    'user' => $firstSquare?->user,
                    'squares' => $userSquares,
                    'total_squares' => $userSquares->count(),
                    'paid_squares' => $userSquares->where('is_paid', true)->count(),
                    'unpaid_squares' => $userSquares->where('is_paid', false)->count(),
                    'total_owed' => $totalOwed,
                    'total_paid' => $totalPaid,
                    'balance' => $totalOwed - $totalPaid,
                ];
            })
            ->sortByDesc('balance');

        return view('boards.manage.payments', [
            'board' => $board,
            'squares' => $squares,
            'squaresByUser' => $squaresByUser,
            'totalSquares' => $totalSquares,
            'paidSquares' => $paidSquares,
            'unpaidSquares' => $unpaidSquares,
            'totalExpected' => $totalExpected,
            'totalCollected' => $totalCollected,
            'totalOutstanding' => $totalOutstanding,
        ]);
    }

    /**
     * Mark a square as paid.
     */
    public function markPaid(Board $board, Square $square): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', $board);

        // Ensure the square belongs to this board
        if ($square->board_id !== $board->id) {
            abort(404);
        }

        // Ensure the square is claimed
        if ($square->user_id === null) {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'Cannot mark an unclaimed square as paid.'], 422);
            }

            return back()->withErrors(['square' => 'Cannot mark an unclaimed square as paid.']);
        }

        $square->update([
            'is_paid' => true,
            'paid_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Square marked as paid.',
                'square' => [
                    'id' => $square->id,
                    'is_paid' => true,
                ],
            ]);
        }

        return back()->with('success', 'Square marked as paid.');
    }

    /**
     * Mark a square as unpaid.
     */
    public function markUnpaid(Board $board, Square $square): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', $board);

        // Ensure the square belongs to this board
        if ($square->board_id !== $board->id) {
            abort(404);
        }

        $square->update([
            'is_paid' => false,
            'paid_at' => null,
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Square marked as unpaid.',
                'square' => [
                    'id' => $square->id,
                    'is_paid' => false,
                ],
            ]);
        }

        return back()->with('success', 'Square marked as unpaid.');
    }

    /**
     * Mark multiple squares as paid.
     */
    public function bulkMarkPaid(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('manage', $board);

        $validated = $request->validate([
            'square_ids' => ['required', 'array', 'min:1'],
            'square_ids.*' => ['required', 'integer', 'exists:squares,id'],
        ]);

        $squareIds = $validated['square_ids'];

        // Ensure all squares belong to this board and are claimed
        $squares = Square::whereIn('id', $squareIds)
            ->where('board_id', $board->id)
            ->whereNotNull('user_id')
            ->get();

        if ($squares->isEmpty()) {
            return back()->withErrors(['squares' => 'No valid squares selected.']);
        }

        $squares->each(fn (Square $square) => $square->update([
            'is_paid' => true,
            'paid_at' => now(),
        ]));

        $count = $squares->count();

        return back()->with('success', "{$count} square(s) marked as paid.");
    }

    /**
     * Release multiple squares.
     */
    public function bulkRelease(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('manage', $board);

        $validated = $request->validate([
            'square_ids' => ['required', 'array', 'min:1'],
            'square_ids.*' => ['required', 'integer', 'exists:squares,id'],
        ]);

        $squareIds = $validated['square_ids'];

        // Ensure all squares belong to this board and are claimed
        $squares = Square::whereIn('id', $squareIds)
            ->where('board_id', $board->id)
            ->whereNotNull('user_id')
            ->get();

        if ($squares->isEmpty()) {
            return back()->withErrors(['squares' => 'No valid squares selected.']);
        }

        $squares->each(fn (Square $square) => $square->update([
            'user_id' => null,
            'claimed_at' => null,
            'is_paid' => false,
            'paid_at' => null,
        ]));

        $count = $squares->count();

        return back()->with('success', "{$count} square(s) released.");
    }
}
