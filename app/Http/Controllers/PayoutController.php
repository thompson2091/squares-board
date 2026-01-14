<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\PayoutRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    /**
     * Display the payout rules configuration page.
     */
    public function index(Board $board): View
    {
        $this->authorize('manage', $board);

        $payoutRules = $board->payoutRules()
            ->orderByRaw("FIELD(quarter, 'Q1', 'Q2', 'Q3', 'final')")
            ->orderByRaw("FIELD(winner_type, 'primary', 'reverse', 'touching', '2mw')")
            ->get();

        // Calculate totals for validation display (accounting for touching x4)
        $totalPercentage = 0;
        $totalFixedCents = 0;

        foreach ($payoutRules as $rule) {
            $multiplier = $rule->winner_type === 'touching' ? 4 : 1;

            if ($rule->payout_type === 'percentage') {
                $totalPercentage += $rule->amount * $multiplier;
            } else {
                $totalFixedCents += $rule->amount * $multiplier;
            }
        }

        $potentialPot = 100 * ($board->price_per_square ?? 0);

        return view('boards.manage.payouts', [
            'board' => $board,
            'payoutRules' => $payoutRules,
            'quarters' => PayoutRule::QUARTERS,
            'quarterLabels' => PayoutRule::QUARTER_LABELS,
            'winnerTypes' => PayoutRule::WINNER_TYPES,
            'winnerTypeLabels' => PayoutRule::WINNER_TYPE_LABELS,
            'payoutTypes' => PayoutRule::PAYOUT_TYPES,
            'totalPercentage' => $totalPercentage,
            'totalFixedCents' => $totalFixedCents,
            'potentialPot' => $potentialPot,
        ]);
    }

    /**
     * Store a new payout rule (or multiple rules if multiple quarters selected).
     */
    public function store(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('manage', $board);

        $validated = $request->validate([
            'quarters' => ['required', 'array', 'min:1'],
            'quarters.*' => ['required', 'string', 'in:'.implode(',', PayoutRule::QUARTERS)],
            'payout_type' => ['required', 'string', 'in:'.implode(',', PayoutRule::PAYOUT_TYPES)],
            'amount' => ['required', 'numeric', 'min:0'],
            'winner_type' => ['required', 'string', 'in:'.implode(',', PayoutRule::WINNER_TYPES)],
        ]);

        // Convert user-friendly amount to storage format
        // Percentage: 25 (for 25%) → 2500 (basis points)
        // Fixed: 25.00 (for $25) → 2500 (cents)
        $amountForStorage = (int) round((float) $validated['amount'] * 100);

        /** @var array<string> $quarters */
        $quarters = $validated['quarters'];

        // Filter quarters for winner types that have restrictions (e.g., 2mw only for Q2/final)
        if (isset(PayoutRule::WINNER_TYPE_QUARTERS[$validated['winner_type']])) {
            $allowedQuarters = PayoutRule::WINNER_TYPE_QUARTERS[$validated['winner_type']];
            $quarters = array_filter($quarters, fn ($q) => in_array($q, $allowedQuarters, true));

            if (empty($quarters)) {
                return back()
                    ->withInput()
                    ->withErrors(['quarters' => PayoutRule::WINNER_TYPE_LABELS[$validated['winner_type']].' is only available for '.implode(' and ', array_map(fn ($q) => PayoutRule::QUARTER_LABELS[$q], $allowedQuarters)).'.']);
            }
        }
        $duplicates = [];
        $created = 0;

        // Check for duplicates first
        foreach ($quarters as $quarter) {
            $exists = $board->payoutRules()
                ->where('quarter', $quarter)
                ->where('winner_type', $validated['winner_type'])
                ->exists();

            if ($exists) {
                $duplicates[] = PayoutRule::QUARTER_LABELS[$quarter] ?? $quarter;
            }
        }

        if (count($duplicates) === count($quarters)) {
            return back()
                ->withInput()
                ->withErrors(['quarters' => 'Payout rules for all selected periods and this winner type already exist.']);
        }

        // Validate total doesn't exceed pot (check with all new rules)
        $ruleForValidation = [
            'payout_type' => $validated['payout_type'],
            'amount' => $amountForStorage * (count($quarters) - count($duplicates)),
            'winner_type' => $validated['winner_type'],
        ];
        $validationResult = $this->validateTotalPayouts($board, $ruleForValidation);
        if ($validationResult !== null) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $validationResult]);
        }

        // Create rules for non-duplicate quarters
        foreach ($quarters as $quarter) {
            $exists = $board->payoutRules()
                ->where('quarter', $quarter)
                ->where('winner_type', $validated['winner_type'])
                ->exists();

            if (! $exists) {
                $board->payoutRules()->create([
                    'quarter' => $quarter,
                    'payout_type' => $validated['payout_type'],
                    'amount' => $amountForStorage,
                    'winner_type' => $validated['winner_type'],
                ]);
                $created++;
            }
        }

        $message = $created === 1
            ? 'Payout rule created successfully.'
            : "{$created} payout rules created successfully.";

        if (count($duplicates) > 0) {
            $message .= ' Skipped existing: '.implode(', ', $duplicates).'.';
        }

        return back()->with('success', $message);
    }

    /**
     * Update an existing payout rule.
     */
    public function update(Request $request, Board $board, PayoutRule $payout): RedirectResponse
    {
        $this->authorize('manage', $board);

        // Ensure the payout rule belongs to this board
        if ($payout->board_id !== $board->id) {
            abort(404);
        }

        $validated = $request->validate([
            'quarter' => ['required', 'string', 'in:'.implode(',', PayoutRule::QUARTERS)],
            'payout_type' => ['required', 'string', 'in:'.implode(',', PayoutRule::PAYOUT_TYPES)],
            'amount' => ['required', 'numeric', 'min:0'],
            'winner_type' => ['required', 'string', 'in:'.implode(',', PayoutRule::WINNER_TYPES)],
        ]);

        // Convert user-friendly amount to storage format
        // Percentage: 25 (for 25%) → 2500 (basis points)
        // Fixed: 25.00 (for $25) → 2500 (cents)
        $amountForStorage = (int) round((float) $validated['amount'] * 100);
        $validated['amount'] = $amountForStorage;

        // Check for duplicate (excluding current rule)
        $exists = $board->payoutRules()
            ->where('id', '!=', $payout->id)
            ->where('quarter', $validated['quarter'])
            ->where('winner_type', $validated['winner_type'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['quarter' => 'A payout rule for this quarter and winner type already exists.']);
        }

        // Validate total doesn't exceed pot (excluding current rule's amount)
        $validationResult = $this->validateTotalPayouts($board, $validated, $payout->id);
        if ($validationResult !== null) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $validationResult]);
        }

        $payout->update([
            'quarter' => $validated['quarter'],
            'payout_type' => $validated['payout_type'],
            'amount' => $validated['amount'],
            'winner_type' => $validated['winner_type'],
        ]);

        return back()->with('success', 'Payout rule updated successfully.');
    }

    /**
     * Delete a payout rule.
     */
    public function destroy(Board $board, PayoutRule $payout): RedirectResponse
    {
        $this->authorize('manage', $board);

        // Ensure the payout rule belongs to this board
        if ($payout->board_id !== $board->id) {
            abort(404);
        }

        $payout->delete();

        return back()->with('success', 'Payout rule deleted successfully.');
    }

    /**
     * Validate that total payouts don't exceed the pot.
     *
     * @param  array<string, mixed>  $newRule
     * @return string|null Error message if validation fails, null if valid
     */
    private function validateTotalPayouts(Board $board, array $newRule, ?int $excludeRuleId = null): ?string
    {
        $potentialPot = 100 * ($board->price_per_square ?? 0);

        if ($potentialPot === 0) {
            return null; // Can't validate without knowing pot size
        }

        $existingRules = $board->payoutRules();
        if ($excludeRuleId !== null) {
            $existingRules->where('id', '!=', $excludeRuleId);
        }
        $existingRules = $existingRules->get();

        // Calculate existing totals (accounting for touching x4)
        $totalPercentage = 0;
        $totalFixed = 0;

        foreach ($existingRules as $rule) {
            $multiplier = $rule->winner_type === 'touching' ? 4 : 1;

            if ($rule->payout_type === 'percentage') {
                $totalPercentage += $rule->amount * $multiplier;
            } else {
                $totalFixed += $rule->amount * $multiplier;
            }
        }

        // Add new rule (check if it's a touching rule)
        $newRuleMultiplier = ($newRule['winner_type'] ?? '') === 'touching' ? 4 : 1;

        if ($newRule['payout_type'] === 'percentage') {
            $totalPercentage += $newRule['amount'] * $newRuleMultiplier;
        } else {
            $totalFixed += $newRule['amount'] * $newRuleMultiplier;
        }

        // Check if percentage exceeds 100%
        if ($totalPercentage > 10000) {
            $touchingNote = $newRuleMultiplier === 4 ? ' (Note: Touching pays 4 squares)' : '';

            return 'Total percentage payouts would exceed 100%. Please reduce the amount.'.$touchingNote;
        }

        // Calculate total payout in cents
        $totalPayoutCents = ($potentialPot * $totalPercentage / 10000) + $totalFixed;

        if ($totalPayoutCents > $potentialPot) {
            $touchingNote = $newRuleMultiplier === 4 ? ' Note: Touching pays 4 squares, so the total is 4x the entered amount.' : '';

            return sprintf(
                'Total payouts ($%s) would exceed the potential pot ($%s). Please reduce the amount.%s',
                number_format($totalPayoutCents / 100, 2),
                number_format($potentialPot / 100, 2),
                $touchingNote
            );
        }

        return null;
    }
}
