<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Board;
use App\Models\PayoutRule;
use App\Models\Square;
use Illuminate\Support\Collection;

/**
 * Service for calculating winners based on game scores and board configuration.
 */
class WinnerCalculatorService
{
    /**
     * Calculate winners for a given quarter and score.
     *
     * @param  Board  $board  The board to calculate winners for
     * @param  string  $quarter  The quarter (Q1, Q2, Q3, final)
     * @param  int  $rowScore  The row team's score
     * @param  int  $colScore  The column team's score
     * @param  int|null  $row2mwScore  The row team's 2-minute warning score (Q2/final only)
     * @param  int|null  $col2mwScore  The column team's 2-minute warning score (Q2/final only)
     * @return Collection<int, array<string, mixed>> Collection of winner data arrays
     */
    public function calculateWinners(
        Board $board,
        string $quarter,
        int $rowScore,
        int $colScore,
        ?int $row2mwScore = null,
        ?int $col2mwScore = null
    ): Collection {
        /** @var Collection<int, array<string, mixed>> $winners */
        $winners = collect();

        // Get last digit of each score
        $rowDigit = $rowScore % 10;
        $colDigit = $colScore % 10;

        // Get the board's number arrays
        /** @var array<int, int> $rowNumbers */
        $rowNumbers = $board->row_numbers ?? [];
        /** @var array<int, int> $colNumbers */
        $colNumbers = $board->col_numbers ?? [];

        if (empty($rowNumbers) || empty($colNumbers)) {
            return $winners;
        }

        // Find winning position using board's number arrays
        $winningRow = $this->findPosition($rowNumbers, $rowDigit);
        $winningCol = $this->findPosition($colNumbers, $colDigit);

        if ($winningRow === null || $winningCol === null) {
            return $winners;
        }

        // Get payout rules for this quarter
        $payoutRules = $board->payoutRules()
            ->where('quarter', $quarter)
            ->get();

        // Calculate pot total for percentage-based payouts
        $potTotal = $this->calculatePotTotal($board);

        foreach ($payoutRules as $rule) {
            /** @var PayoutRule $rule */
            if ($rule->winner_type === '2mw') {
                // 2-minute warning winner: use 2MW scores (only for Q2/final)
                if ($row2mwScore === null || $col2mwScore === null) {
                    continue; // Skip if no 2MW scores provided
                }

                $row2mwDigit = $row2mwScore % 10;
                $col2mwDigit = $col2mwScore % 10;

                $winning2mwRow = $this->findPosition($rowNumbers, $row2mwDigit);
                $winning2mwCol = $this->findPosition($colNumbers, $col2mwDigit);

                if ($winning2mwRow !== null && $winning2mwCol !== null) {
                    $winnerData = $this->createWinnerData(
                        $board,
                        $winning2mwRow,
                        $winning2mwCol,
                        $quarter,
                        $row2mwDigit,
                        $col2mwDigit,
                        $rule->calculatePayout($potTotal),
                        false,
                        false,
                        true,
                        '2-minute warning'
                    );

                    if ($winnerData !== null) {
                        $winners->push($winnerData);
                    }
                }
            } elseif ($rule->winner_type === 'reverse') {
                // Reverse winner: swap the digits
                $reverseRow = $this->findPosition($rowNumbers, $colDigit);
                $reverseCol = $this->findPosition($colNumbers, $rowDigit);

                if ($reverseRow !== null && $reverseCol !== null) {
                    $winnerData = $this->createWinnerData(
                        $board,
                        $reverseRow,
                        $reverseCol,
                        $quarter,
                        $colDigit,
                        $rowDigit,
                        $rule->calculatePayout($potTotal),
                        true,
                        false
                    );

                    if ($winnerData !== null) {
                        $winners->push($winnerData);
                    }
                }
            } elseif ($rule->winner_type === 'touching') {
                // Touching squares: 4-way adjacent with wrap-around
                $touchingPositions = $this->getTouchingPositions($winningRow, $winningCol);

                foreach ($touchingPositions as $position) {
                    $winnerData = $this->createWinnerData(
                        $board,
                        $position['row'],
                        $position['col'],
                        $quarter,
                        $rowDigit,
                        $colDigit,
                        $rule->calculatePayout($potTotal),
                        false,
                        true,
                        false
                    );

                    if ($winnerData !== null) {
                        $winners->push($winnerData);
                    }
                }
            } else {
                // Primary winner
                $winnerData = $this->createWinnerData(
                    $board,
                    $winningRow,
                    $winningCol,
                    $quarter,
                    $rowDigit,
                    $colDigit,
                    $rule->calculatePayout($potTotal),
                    false,
                    false
                );

                if ($winnerData !== null) {
                    $winners->push($winnerData);
                }
            }
        }

        return $winners;
    }

    /**
     * Find the position of a digit in the number array.
     *
     * @param  array<int, int>  $numbers  The number array
     * @param  int  $digit  The digit to find (0-9)
     * @return int|null The position (0-9) or null if not found
     */
    private function findPosition(array $numbers, int $digit): ?int
    {
        $position = array_search($digit, $numbers, true);

        return $position !== false ? (int) $position : null;
    }

    /**
     * Get the 4 touching positions with wrap-around.
     *
     * @param  int  $row  The center row position
     * @param  int  $col  The center column position
     * @return array<int, array{row: int, col: int}> Array of touching positions
     */
    private function getTouchingPositions(int $row, int $col): array
    {
        return [
            ['row' => ($row - 1 + 10) % 10, 'col' => $col],           // up
            ['row' => ($row + 1) % 10, 'col' => $col],                // down
            ['row' => $row, 'col' => ($col - 1 + 10) % 10],           // left
            ['row' => $row, 'col' => ($col + 1) % 10],                // right
        ];
    }

    /**
     * Create winner data array from square position.
     *
     * @param  Board  $board  The board
     * @param  int  $row  Row position (0-9)
     * @param  int  $col  Column position (0-9)
     * @param  string  $quarter  The quarter
     * @param  int  $rowScore  Row team score digit
     * @param  int  $colScore  Column team score digit
     * @param  int  $payoutAmount  Payout amount in cents
     * @param  bool  $isReverse  Whether this is a reverse winner
     * @param  bool  $isTouching  Whether this is a touching square winner
     * @param  bool  $is2mw  Whether this is a 2-minute warning winner
     * @param  string|null  $notes  Optional notes
     * @return array<string, mixed>|null Winner data or null if square not found/claimed
     */
    private function createWinnerData(
        Board $board,
        int $row,
        int $col,
        string $quarter,
        int $rowScore,
        int $colScore,
        int $payoutAmount,
        bool $isReverse,
        bool $isTouching,
        bool $is2mw = false,
        ?string $notes = null
    ): ?array {
        /** @var Square|null $square */
        $square = $board->squares()
            ->where('row', $row)
            ->where('col', $col)
            ->whereNotNull('user_id')
            ->first();

        if ($square === null || $square->user_id === null) {
            return null;
        }

        return [
            'board_id' => $board->id,
            'square_id' => $square->id,
            'user_id' => $square->user_id,
            'quarter' => $quarter,
            'row_score' => $rowScore,
            'col_score' => $colScore,
            'payout_amount' => $payoutAmount,
            'is_reverse' => $isReverse,
            'is_touching' => $isTouching,
            'is_2mw' => $is2mw,
            'notes' => $notes,
        ];
    }

    /**
     * Calculate the total pot for a board based on claimed squares.
     *
     * @param  Board  $board  The board
     * @return int Total pot in cents
     */
    private function calculatePotTotal(Board $board): int
    {
        $claimedSquares = $board->squares()
            ->whereNotNull('user_id')
            ->count();

        /** @var int $pricePerSquare */
        $pricePerSquare = $board->price_per_square ?? 0;

        return $claimedSquares * $pricePerSquare;
    }
}
