<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Square;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BoardController extends Controller
{
    /**
     * Display a listing of the user's boards.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        // Get boards owned by user
        $ownedBoards = Board::where('owner_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get boards where user is a co-admin
        $adminBoards = Board::whereHas('admins', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->orderBy('created_at', 'desc')
            ->get();

        // Get boards where user has claimed squares
        $participatingBoards = Board::whereHas('squares', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->where('owner_id', '!=', $user->id)
            ->whereDoesntHave('admins', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('boards.index', [
            'ownedBoards' => $ownedBoards,
            'adminBoards' => $adminBoards,
            'participatingBoards' => $participatingBoards,
        ]);
    }

    /**
     * Display public boards available for joining.
     */
    public function browse(): View
    {
        $boards = Board::where('is_public', true)
            ->where('status', Board::STATUS_OPEN)
            ->withCount(['squares as claimed_count' => function ($query): void {
                $query->whereNotNull('user_id');
            }])
            ->orderBy('game_date', 'asc')
            ->paginate(12);

        return view('boards.browse', [
            'boards' => $boards,
        ]);
    }

    /**
     * Show the form for creating a new board.
     */
    public function create(): View
    {
        return view('boards.create');
    }

    /**
     * Store a newly created board in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:boards,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'team_row' => ['required', 'string', 'max:100'],
            'team_col' => ['required', 'string', 'max:100'],
            'game_date' => ['nullable', 'date'],
            'price_per_square' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'max_squares_per_user' => ['required', 'integer', 'min:1', 'max:100'],
            'is_public' => ['boolean'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        // Convert price from dollars to cents
        $validated['price_per_square'] = (int) round((float) $validated['price_per_square'] * 100);
        $validated['owner_id'] = $user->id;
        $validated['status'] = Board::STATUS_DRAFT;
        $validated['is_public'] = $validated['is_public'] ?? false;
        // Convert empty slug to null
        $validated['slug'] = $validated['slug'] ?: null;

        $board = DB::transaction(function () use ($validated): Board {
            $board = Board::create($validated);

            // Generate all 100 squares
            $squares = [];
            $now = now();

            for ($row = 0; $row < 10; $row++) {
                for ($col = 0; $col < 10; $col++) {
                    $squares[] = [
                        'board_id' => $board->id,
                        'row' => $row,
                        'col' => $col,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            Square::insert($squares);

            return $board;
        });

        return redirect()->route('manage.boards.payouts.index', $board)
            ->with('success', 'Board created! Now set up your payout rules.');
    }

    /**
     * Display the specified board.
     */
    public function show(Request $request, Board $board): View
    {
        $board->load(['squares.user', 'owner', 'payoutRules', 'winners', 'gameScores']);

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
            ->groupBy(function (\App\Models\Winner $winner): string {
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

        return view('boards.show', [
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
        ]);
    }

    /**
     * Process a pending square claim after user authentication.
     *
     * @param  array{board_uuid: string, row: int, col: int}  $pendingClaim
     * @return array{success: bool, message: string}
     */
    private function processPendingClaim(Board $board, User $user, array $pendingClaim): array
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

    /**
     * Show the board management dashboard.
     */
    public function manage(Board $board): View
    {
        Gate::authorize('update', $board);

        $claimedCount = $board->squares()->whereNotNull('user_id')->count();
        $paidCount = $board->squares()->whereNotNull('user_id')->where('is_paid', true)->count();

        return view('boards.manage.index', [
            'board' => $board,
            'claimedCount' => $claimedCount,
            'paidCount' => $paidCount,
        ]);
    }

    /**
     * Show the form for editing the specified board.
     */
    public function edit(Board $board): View
    {
        Gate::authorize('update', $board);

        return view('boards.edit', [
            'board' => $board,
        ]);
    }

    /**
     * Update the specified board in storage.
     */
    public function update(Request $request, Board $board): RedirectResponse
    {
        Gate::authorize('update', $board);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:boards,slug,'.$board->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'team_row' => ['required', 'string', 'max:100'],
            'team_col' => ['required', 'string', 'max:100'],
            'game_date' => ['nullable', 'date'],
            'price_per_square' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'max_squares_per_user' => ['required', 'integer', 'min:1', 'max:100'],
            'is_public' => ['boolean'],
            'status' => ['sometimes', 'string', 'in:draft,open,locked,completed'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        // Convert price from dollars to cents
        $validated['price_per_square'] = (int) round((float) $validated['price_per_square'] * 100);
        $validated['is_public'] = $validated['is_public'] ?? false;
        // Convert empty slug to null
        $validated['slug'] = $validated['slug'] ?: null;

        $board->update($validated);

        return redirect()->route('boards.show', $board)
            ->with('success', 'Board updated successfully!');
    }

    /**
     * Remove the specified board from storage.
     */
    public function destroy(Board $board): RedirectResponse
    {
        Gate::authorize('delete', $board);

        $board->delete();

        return redirect()->route('boards.index')
            ->with('success', 'Board deleted successfully!');
    }

    /**
     * Lock the board to prevent further square claims.
     */
    public function lock(Board $board): RedirectResponse
    {
        Gate::authorize('update', $board);

        if ($board->status !== Board::STATUS_OPEN) {
            return redirect()->route('boards.show', $board)
                ->with('error', 'Board can only be locked when it is open.');
        }

        // Generate random numbers if not already generated
        if ($board->row_numbers === null || $board->col_numbers === null) {
            $board->generateNumbers();
        }

        $board->update([
            'status' => Board::STATUS_LOCKED,
            'numbers_revealed' => true,
        ]);

        return redirect()->route('boards.show', $board)
            ->with('success', 'Board has been locked and numbers revealed!');
    }

    /**
     * Generate random numbers for the board.
     */
    public function generateNumbers(Board $board): RedirectResponse
    {
        Gate::authorize('update', $board);

        // Only allow if board is full
        if (! $board->isFull()) {
            return redirect()->route('manage.boards.show', $board)
                ->with('error', 'Numbers can only be generated when all 100 squares are claimed.');
        }

        // Only generate if not already generated
        if ($board->row_numbers !== null && $board->col_numbers !== null) {
            return redirect()->route('manage.boards.show', $board)
                ->with('error', 'Numbers have already been generated.');
        }

        $board->generateNumbers();

        return redirect()->route('manage.boards.show', $board)
            ->with('success', 'Numbers have been generated successfully!');
    }
}
