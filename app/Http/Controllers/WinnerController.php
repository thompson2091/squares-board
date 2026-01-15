<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\GameScore;
use Illuminate\View\View;

class WinnerController extends Controller
{
    /**
     * Display the winners for a board.
     */
    public function index(Board $board): View
    {
        $this->authorize('manage', $board);

        $winners = $board->winners()
            ->with(['user', 'square'])
            ->orderBy('quarter')
            ->orderBy('is_reverse')
            ->orderBy('is_touching')
            ->get();

        $gameScores = $board->gameScores()
            ->orderBy('quarter')
            ->get()
            ->keyBy('quarter');

        // Group winners by quarter
        $winnersByQuarter = $winners->groupBy('quarter');

        // Calculate total payouts
        $totalPayouts = $winners->sum('payout_amount');

        // Calculate payouts by display name
        // Group by display name so same user with different square names shows separately
        $payoutsByDisplayName = $winners
            ->groupBy(function (\App\Models\Winner $winner): string {
                return $winner->square->displayNameForSquare ?? $winner->user->name;
            })
            ->map(function ($winners, $displayName) {
                /** @var \Illuminate\Support\Collection<int, \App\Models\Winner> $winners */
                $firstWinner = $winners->first();

                return [
                    'display_name' => $displayName,
                    'user' => $firstWinner?->user,
                    'total' => $winners->sum('payout_amount'),
                    'wins' => $winners->count(),
                ];
            })
            ->sortByDesc('total');

        return view('boards.manage.winners', [
            'board' => $board,
            'winners' => $winners,
            'winnersByQuarter' => $winnersByQuarter,
            'gameScores' => $gameScores,
            'totalPayouts' => $totalPayouts,
            'payoutsByDisplayName' => $payoutsByDisplayName,
            'quarters' => GameScore::QUARTERS,
        ]);
    }
}
