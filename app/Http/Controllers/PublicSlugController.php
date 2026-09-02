<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Resolves the short, shareable URLs at the root: `/{slug}`.
 *
 * Seasons and boards share this namespace (AvailableSlug keeps them from
 * colliding), so a slug is tried as a season first and falls back to a board.
 */
class PublicSlugController extends Controller
{
    public function __construct(
        private readonly SeasonController $seasons,
        private readonly BoardController $boards,
    ) {}

    /**
     * The season's current week, or a standalone board.
     */
    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $season = $this->findSeason($slug);

        if ($season !== null) {
            return $this->seasons->show($request, $season);
        }

        return $this->boards->show($request, $this->findBoardOrFail($slug));
    }

    /**
     * A single week of a season.
     */
    public function week(Request $request, string $slug, int $week): View
    {
        return $this->seasons->showWeek($request, $this->findSeasonOrFail($slug), $week);
    }

    /**
     * A season's roster.
     */
    public function roster(Request $request, string $slug): View
    {
        return $this->seasons->showRoster($request, $this->findSeasonOrFail($slug));
    }

    /**
     * A season's standings.
     */
    public function standings(Request $request, string $slug): View
    {
        return $this->seasons->showStandings($request, $this->findSeasonOrFail($slug));
    }

    private function findSeason(string $slug): ?Season
    {
        return Season::where('slug', $slug)->first()
            ?? Season::where('uuid', $slug)->first();
    }

    private function findSeasonOrFail(string $slug): Season
    {
        $season = $this->findSeason($slug);

        if ($season === null) {
            abort(404);
        }

        return $season;
    }

    private function findBoardOrFail(string $slug): Board
    {
        $board = Board::where('slug', $slug)->first()
            ?? Board::where('uuid', $slug)->first();

        if ($board === null) {
            abort(404);
        }

        return $board;
    }
}
