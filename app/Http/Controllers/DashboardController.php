<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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

        // Load owned boards and participated boards
        $ownedBoards = $user->ownedBoards()
            ->withCount(['squares as claimed_count' => function ($query): void {
                $query->whereNotNull('user_id');
            }])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $participatedBoards = $user->squares()
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

        // Get winnings
        $totalWinnings = $user->winnings()->sum('payout_amount');
        $recentWinnings = $user->winnings()
            ->with('board:id,name,uuid')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'ownedBoards' => $ownedBoards,
            'participatedBoards' => $participatedBoards,
            'totalWinnings' => $totalWinnings,
            'recentWinnings' => $recentWinnings,
        ]);
    }
}
