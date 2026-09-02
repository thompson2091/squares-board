<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Season;
use App\Services\SeasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

/**
 * The organizer's side of a season: starting it, and filling in each week's
 * matchup as the schedule firms up.
 */
class SeasonManageController extends Controller
{
    public function __construct(private readonly SeasonService $seasons) {}

    /**
     * The season dashboard - every week's matchup in one editable table.
     */
    public function index(Season $season): View
    {
        Gate::authorize('manage', $season);

        $season->loadMissing(['rosterBoard.payoutRules', 'rosterBoard.squares', 'weekBoards.gameScores']);

        $roster = $season->rosterBoard;

        return view('seasons.manage', [
            'season' => $season,
            'claimedCount' => $roster?->claimedSquareCount() ?? 0,
            'paidCount' => $roster?->paidSquareCount() ?? 0,
            'payoutRules' => $season->payoutRules,
            'matchupsSet' => $season->weeks->filter(fn ($week): bool => $week->has_matchup)->count(),
        ]);
    }

    /**
     * Create the weekly boards without closing signups, so the schedule can go
     * up before anyone has to claim a square blind.
     */
    public function createWeeks(Season $season): RedirectResponse
    {
        Gate::authorize('start', $season);

        try {
            $created = $this->seasons->createWeeks($season);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('manage.seasons.index', $season)->with(
            'success',
            $created === 0
                ? 'Every week already exists - fill in the matchups below.'
                : sprintf('%d weeks added. Fill in the matchups below; signups stay open.', $created)
        );
    }

    /**
     * Create any missing weekly boards and close signups.
     */
    public function start(Season $season): RedirectResponse
    {
        Gate::authorize('start', $season);

        if ($season->payoutRules->isEmpty()) {
            return back()->with('error', 'Set up your payout rules before starting the season.');
        }

        try {
            $this->seasons->start($season);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('manage.seasons.index', $season)->with(
            'success',
            sprintf('Season started! %d weeks are live and signups are closed.', $season->total_weeks)
        );
    }

    /**
     * Save the matchups and kickoff times from the bulk table.
     */
    public function updateWeeks(Request $request, Season $season): RedirectResponse
    {
        Gate::authorize('manage', $season);

        $validated = $request->validate([
            'weeks' => ['required', 'array'],
            'weeks.*.team_row' => ['nullable', 'string', 'max:100'],
            'weeks.*.team_col' => ['nullable', 'string', 'max:100'],
            'weeks.*.game_date' => ['nullable', 'date'],
        ]);

        $season->loadMissing('weekBoards');

        $changed = $this->seasons->updateWeeks($season, $validated['weeks']);

        return back()->with('success', $changed === 0
            ? 'No changes to save.'
            : sprintf('%d week(s) updated.', $changed));
    }

    /**
     * Reveal a week's numbers, drawing them first if needed.
     */
    public function reveal(Season $season, int $week): RedirectResponse
    {
        Gate::authorize('manage', $season);

        $season->loadMissing('weekBoards');

        $board = $season->weekBoard($week);

        if ($board === null) {
            abort(404);
        }

        $this->seasons->revealWeek($board);

        return back()->with('success', sprintf('Week %d numbers revealed.', $week));
    }
}
