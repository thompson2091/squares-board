<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\GameScore;
use App\Services\WinnerCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScoreController extends Controller
{
    /**
     * Display the score entry page.
     */
    public function index(Board $board): View
    {
        $this->authorize('manage', $board);

        $gameScores = $board->gameScores()
            ->orderByRaw("FIELD(quarter, 'Q1', 'Q2', 'Q3', 'final')")
            ->get()
            ->keyBy('quarter');

        // Calculate which quarters have winners
        $quartersWithWinners = $board->winners()
            ->select('quarter')
            ->distinct()
            ->pluck('quarter')
            ->toArray();

        // Get quarters that have 2MW payout rules configured
        $quartersWith2mw = $board->payoutRules()
            ->where('winner_type', '2mw')
            ->pluck('quarter')
            ->toArray();

        return view('boards.manage.scores', [
            'board' => $board,
            'gameScores' => $gameScores,
            'quartersWithWinners' => $quartersWithWinners,
            'quartersWith2mw' => $quartersWith2mw,
            'quarters' => GameScore::QUARTERS,
        ]);
    }

    /**
     * Store or update a game score and auto-calculate winners.
     */
    public function store(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('manage', $board);

        $validated = $request->validate([
            'quarter' => ['required', 'string', 'in:'.implode(',', GameScore::QUARTERS)],
            'team_row_score' => ['required', 'integer', 'min:0', 'max:999'],
            'team_col_score' => ['required', 'integer', 'min:0', 'max:999'],
            'team_row_2mw_score' => ['nullable', 'integer', 'min:0', 'max:999'],
            'team_col_2mw_score' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_final' => ['sometimes', 'boolean'],
        ]);

        // Check if board has numbers assigned
        if (empty($board->row_numbers) || empty($board->col_numbers)) {
            return back()
                ->withInput()
                ->withErrors(['quarter' => 'Cannot record scores until numbers have been generated for the board.']);
        }

        // Check if score already exists for this quarter
        $existingScore = $board->gameScores()
            ->where('quarter', $validated['quarter'])
            ->first();

        // Prepare score data
        $scoreData = [
            'team_row_score' => $validated['team_row_score'],
            'team_col_score' => $validated['team_col_score'],
            'is_final' => $validated['is_final'] ?? false,
            'source' => 'manual',
        ];

        // Include 2MW scores only for Q2 and final
        if (in_array($validated['quarter'], ['Q2', 'final'], true)) {
            $scoreData['team_row_2mw_score'] = $validated['team_row_2mw_score'] ?? null;
            $scoreData['team_col_2mw_score'] = $validated['team_col_2mw_score'] ?? null;
        }

        if ($existingScore !== null) {
            // Update existing score
            $existingScore->update($scoreData);

            // Delete existing winners for this quarter before recalculating
            $board->winners()
                ->where('quarter', $validated['quarter'])
                ->delete();

            $score = $existingScore;
        } else {
            // Create new score
            $scoreData['quarter'] = $validated['quarter'];
            $score = $board->gameScores()->create($scoreData);
        }

        // Auto-calculate winners
        $winnerCalculator = app(WinnerCalculatorService::class);
        $winners = $winnerCalculator->calculateWinners(
            $board,
            $validated['quarter'],
            (int) $validated['team_row_score'],
            (int) $validated['team_col_score'],
            isset($validated['team_row_2mw_score']) ? (int) $validated['team_row_2mw_score'] : null,
            isset($validated['team_col_2mw_score']) ? (int) $validated['team_col_2mw_score'] : null
        );

        // Persist winners
        $winnerCount = 0;
        foreach ($winners as $winnerData) {
            $board->winners()->create($winnerData);
            $winnerCount++;
        }

        $message = $existingScore !== null
            ? "Score updated for {$validated['quarter']}."
            : "Score recorded for {$validated['quarter']}.";

        if ($winnerCount > 0) {
            $message .= " {$winnerCount} winner(s) calculated.";
        } else {
            $message .= ' No winners found (squares may not be claimed).';
        }

        return back()->with('success', $message);
    }

    /**
     * Mark the board as completed.
     */
    public function complete(Board $board): RedirectResponse
    {
        $this->authorize('manage', $board);

        // Verify all quarters are complete before allowing completion
        if (! $this->areAllQuartersComplete($board)) {
            return back()->withErrors(['complete' => 'Cannot complete board until all quarter scores (and 2-minute warning scores if configured) are entered.']);
        }

        $board->update(['status' => Board::STATUS_COMPLETED]);

        return back()->with('success', 'Board marked as completed! All winners have been finalized.');
    }

    /**
     * Check if all required quarters have scores entered.
     */
    private function areAllQuartersComplete(Board $board): bool
    {
        // Get all game scores for this board
        $gameScores = $board->gameScores()->get()->keyBy('quarter');

        // Check if all 4 quarters have scores
        foreach (GameScore::QUARTERS as $quarter) {
            if (! $gameScores->has($quarter)) {
                return false;
            }
        }

        // Get quarters that have 2MW payout rules configured
        $quartersWith2mw = $board->payoutRules()
            ->where('winner_type', '2mw')
            ->pluck('quarter')
            ->toArray();

        // Check if 2MW scores are entered for quarters that require them
        foreach ($quartersWith2mw as $quarter) {
            $score = $gameScores->get($quarter);
            if ($score === null) {
                return false;
            }

            // 2MW requires both row and col 2MW scores
            if ($score->team_row_2mw_score === null || $score->team_col_2mw_score === null) {
                return false;
            }
        }

        return true;
    }
}
