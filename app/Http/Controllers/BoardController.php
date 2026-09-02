<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsBoardView;
use App\Models\Board;
use App\Models\Season;
use App\Rules\AvailableSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BoardController extends Controller
{
    use BuildsBoardView;

    /**
     * Display a listing of the user's boards.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        // Season boards are excluded throughout - a season would otherwise add
        // 19 rows to every list. Seasons get their own section instead.
        $ownedBoards = Board::whereNull('season_id')
            ->where('owner_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get boards where user is a co-admin
        $adminBoards = Board::whereNull('season_id')
            ->whereHas('admins', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Get boards where user has claimed squares
        $participatingBoards = Board::whereNull('season_id')
            ->whereHas('squares', function ($query) use ($user): void {
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
            'seasons' => Season::forUser($user)->get(),
        ]);
    }

    /**
     * Display public boards available for joining.
     */
    public function browse(): View
    {
        $boards = Board::whereNull('season_id')
            ->where('is_public', true)
            ->where('status', Board::STATUS_OPEN)
            ->withCount(['squares as claimed_count' => function ($query): void {
                $query->whereNotNull('user_id');
            }])
            ->orderBy('game_date', 'asc')
            ->paginate(12);

        return view('boards.browse', [
            'boards' => $boards,
            // Season rosters are excluded from the query above, so public
            // seasons would otherwise be undiscoverable.
            'seasons' => Season::publiclyOpen()->limit(12)->get(),
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
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', new AvailableSlug],
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
            $board->seedSquares();

            return $board;
        });

        return redirect()->route('manage.boards.payouts.index', $board)
            ->with('success', 'Board created! Now set up your payout rules.');
    }

    /**
     * Display the specified board.
     *
     * A board belonging to a season is always reached through SeasonController,
     * which adds the season chrome; land people there instead of showing a week
     * stripped of its context.
     */
    public function show(Request $request, Board $board): View|RedirectResponse
    {
        if ($board->season_id !== null && $board->season !== null) {
            return $board->isRoster()
                ? redirect($board->season->rosterUrl())
                : redirect($board->season->weekUrl((int) $board->week_number));
        }

        return view('boards.show', $this->buildBoardView($request, $board));
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
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', new AvailableSlug(ignoreBoardId: $board->id)],
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

        return redirect($board->url)
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
     * Display the print-friendly version of the board.
     */
    public function print(Board $board): View
    {
        // The season chain is what a week's effective payout rules read from.
        $board->load(['squares.user', 'payoutRules', 'season.rosterBoard.payoutRules']);

        // Organize squares into a 10x10 grid
        $grid = [];
        foreach ($board->squares as $square) {
            $grid[$square->row][$square->col] = $square;
        }

        return view('boards.print', [
            'board' => $board,
            'grid' => $grid,
        ]);
    }

    /**
     * Lock the board to prevent further square claims.
     */
    public function lock(Board $board): RedirectResponse
    {
        Gate::authorize('update', $board);

        // Season boards are governed by the season: the roster locks when the
        // season starts, and weeks reveal their numbers one at a time.
        if ($board->season_id !== null && $board->season !== null) {
            return redirect()->route('manage.seasons.index', $board->season)
                ->with('error', 'Season boards are locked from the season dashboard.');
        }

        if ($board->status !== Board::STATUS_OPEN) {
            return redirect($board->url)
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

        return redirect($board->url)
            ->with('success', 'Board has been locked and numbers revealed!');
    }

    /**
     * Generate random numbers for the board.
     */
    public function generateNumbers(Board $board): RedirectResponse
    {
        Gate::authorize('update', $board);

        // Each season week draws and reveals on its own schedule, and unlike a
        // standalone board it does not need a full roster first.
        if ($board->season_id !== null && $board->season !== null) {
            return redirect()->route('manage.seasons.index', $board->season)
                ->with('error', 'Draw a season week\'s numbers from the season dashboard.');
        }

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
