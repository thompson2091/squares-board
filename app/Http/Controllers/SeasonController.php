<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsBoardView;
use App\Models\Board;
use App\Models\Season;
use App\Rules\AvailableSlug;
use App\Services\SeasonService;
use App\Services\SeasonStandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The player-facing side of a season pool: signing up on the roster, viewing a
 * given week, and the season standings.
 */
class SeasonController extends Controller
{
    use BuildsBoardView;

    public function __construct(
        private readonly SeasonService $seasons,
        private readonly SeasonStandingsService $standings,
    ) {}

    /**
     * Show the form for creating a new season.
     */
    public function create(): View
    {
        Gate::authorize('create', Season::class);

        return view('seasons.create');
    }

    /**
     * Store a newly created season and its roster board.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Season::class);

        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', new AvailableSlug],
            'description' => ['nullable', 'string', 'max:1000'],
            'total_weeks' => ['required', 'integer', 'min:2', 'max:30'],
            'price_per_square' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'max_squares_per_user' => ['required', 'integer', 'min:1', 'max:100'],
            'number_draw_mode' => ['required', 'string', 'in:'.implode(',', Season::DRAW_MODES)],
            'is_public' => ['boolean'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        // Convert price from dollars to cents
        $validated['price_per_square'] = (int) round((float) $validated['price_per_square'] * 100);
        $validated['slug'] = $validated['slug'] ?: null;
        $validated['is_public'] = $validated['is_public'] ?? false;

        $season = $this->seasons->create($validated, $user);

        return redirect()->route('manage.boards.payouts.index', $season->rosterBoard)
            ->with('success', 'Season created! Set your payout rules - they apply to every week.');
    }

    /**
     * The shareable season URL: always the current week.
     */
    public function show(Request $request, Season $season): View|RedirectResponse
    {
        $this->loadSeason($season);

        $board = $season->currentWeekBoard();

        // Until the season starts, signups are the point - even when the weeks
        // already exist because the organizer loaded the schedule early.
        if ($board === null || $season->isDraft() || $season->isOpen()) {
            return redirect($season->rosterUrl());
        }

        return $this->renderWeek($request, $season, $board);
    }

    /**
     * A single week of the season.
     */
    public function showWeek(Request $request, Season $season, int $week): View
    {
        $this->loadSeason($season);

        $board = $season->weekBoard($week);

        if ($board === null) {
            abort(404);
        }

        return $this->renderWeek($request, $season, $board);
    }

    /**
     * The roster: where players claim a square for the whole season.
     */
    public function showRoster(Request $request, Season $season): View
    {
        $this->loadSeason($season);

        $roster = $season->rosterBoard;

        if ($roster === null) {
            abort(404);
        }

        return view('seasons.roster', $this->buildBoardView($request, $roster) + [
            'season' => $season,
        ]);
    }

    /**
     * Every week's winnings added together.
     */
    public function showStandings(Request $request, Season $season): View
    {
        $this->loadSeason($season);

        $roster = $season->rosterBoard;
        $claimedCount = $roster?->claimedSquareCount() ?? 0;

        return view('seasons.standings', [
            'season' => $season,
            'standings' => $this->standings->forSeason($season),
            'paidOut' => $this->standings->paidOut($season),
            'myWinnings' => $this->standings->winningsFor($season, $request->user()),
            'claimedCount' => $claimedCount,
            'weeklyPot' => $claimedCount * $season->price_per_square,
            'weeksPlayed' => $season->weeks
                ->filter(fn ($week): bool => $week->isCompleted() || $week->isCurrent())
                ->count(),
            'viewerId' => $request->user()?->id,
        ]);
    }

    /**
     * Show the form for editing the season.
     */
    public function edit(Season $season): View
    {
        Gate::authorize('update', $season);

        $season->load('rosterBoard');

        return view('seasons.edit', ['season' => $season]);
    }

    /**
     * Update the season and mirror the shared settings onto its boards.
     */
    public function update(Request $request, Season $season): RedirectResponse
    {
        Gate::authorize('update', $season);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', new AvailableSlug(ignoreSeasonId: $season->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_per_square' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'max_squares_per_user' => ['required', 'integer', 'min:1', 'max:100'],
            'number_draw_mode' => ['required', 'string', 'in:'.implode(',', Season::DRAW_MODES)],
            'is_public' => ['boolean'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        $pricePerSquare = (int) round((float) $validated['price_per_square'] * 100);

        $season->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: null,
            'description' => $validated['description'] ?? null,
            'number_draw_mode' => $validated['number_draw_mode'],
        ]);

        // Price, limits and payment info live on the boards, so push them down.
        $season->boards()->update([
            'price_per_square' => $pricePerSquare,
            'max_squares_per_user' => (int) $validated['max_squares_per_user'],
            'payment_instructions' => $validated['payment_instructions'] ?? null,
        ]);

        $season->rosterBoard?->update(['is_public' => (bool) ($validated['is_public'] ?? false)]);

        return redirect()->route('manage.seasons.index', $season)
            ->with('success', 'Season updated successfully!');
    }

    /**
     * Delete the season, cascading to its roster and weekly boards.
     */
    public function destroy(Season $season): RedirectResponse
    {
        Gate::authorize('delete', $season);

        $season->delete();

        return redirect()->route('boards.index')
            ->with('success', 'Season deleted successfully!');
    }

    /**
     * Render a week through the standard board view, plus the season chrome.
     */
    private function renderWeek(Request $request, Season $season, Board $board): View
    {
        return view('boards.show', $this->buildBoardView($request, $board) + [
            'season' => $season,
            'seasonWeek' => $season->week((int) $board->week_number),
            'seasonWinnings' => $this->standings->winningsFor($season, $request->user()),
            'seasonStandings' => $this->standings->forSeason($season),
        ]);
    }

    /**
     * Weekly boards read their payout rules from the roster, so load that chain
     * once rather than per week.
     */
    private function loadSeason(Season $season): void
    {
        $season->loadMissing(['rosterBoard.payoutRules', 'weekBoards']);
    }
}
