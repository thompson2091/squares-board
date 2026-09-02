<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Season;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        // Season boards are excluded: one season would otherwise fill the whole
        // dashboard with 19 rows. Seasons are listed separately below.
        $ownedBoards = $user->ownedBoards()
            ->whereNull('season_id')
            ->withCount(['squares as claimed_count' => function ($query): void {
                $query->whereNotNull('user_id');
            }])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $participatedBoards = $user->squares()
            ->whereHas('board', fn ($query) => $query->whereNull('season_id'))
            ->with(['board' => function ($query): void {
                $query->select('id', 'name', 'uuid', 'status', 'price_per_square', 'game_date', 'created_at')
                    ->withCount(['squares as claimed_count' => function ($q): void {
                        $q->whereNotNull('user_id');
                    }]);
            }])
            ->get()
            ->pluck('board')
            ->unique('id')
            ->sortByDesc('created_at')
            ->take(5);

        $seasons = Season::forUser($user)->limit(5)->get();

        // Get winnings
        $totalWinnings = $user->winnings()->sum('payout_amount');
        $recentWinnings = $user->winnings()
            ->with(['board:id,name,uuid', 'square.user:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'ownedBoards' => $ownedBoards,
            'participatedBoards' => $participatedBoards,
            'seasons' => $seasons,
            'totalWinnings' => $totalWinnings,
            'recentWinnings' => $recentWinnings,
        ]);
    }

    /**
     * Display the user's winnings history.
     */
    public function winnings(Request $request): View
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $winnings = $user->winnings()
            ->with(['board:id,name,uuid', 'square.user:id,name'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $totalWinnings = $user->winnings()->sum('payout_amount');

        return view('winnings', [
            'user' => $user,
            'winnings' => $winnings,
            'totalWinnings' => $totalWinnings,
        ]);
    }
}
