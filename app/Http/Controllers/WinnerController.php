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

        // Calculate payouts by user
        $payoutsByUser = $winners->groupBy('user_id')
            ->map(function ($userWinners) {
                /** @var \Illuminate\Support\Collection<int, \App\Models\Winner> $userWinners */
                $firstWinner = $userWinners->first();

                return [
                    'user' => $firstWinner?->user,
                    'total' => $userWinners->sum('payout_amount'),
                    'wins' => $userWinners->count(),
                ];
            })
            ->sortByDesc('total');

        return view('boards.manage.winners', [
            'board' => $board,
            'winners' => $winners,
            'winnersByQuarter' => $winnersByQuarter,
            'gameScores' => $gameScores,
            'totalPayouts' => $totalPayouts,
            'payoutsByUser' => $payoutsByUser,
            'quarters' => GameScore::QUARTERS,
        ]);
    }
}
