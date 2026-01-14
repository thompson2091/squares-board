<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service for generating shuffled number arrays for Super Bowl Squares boards.
 * Uses PHP's shuffle function (Fisher-Yates algorithm) to generate random 0-9 arrays.
 */
class NumberGeneratorService
{
    /**
     * Generate shuffled 0-9 arrays for row and column numbers.
     *
     * @return array{row: list<int>, col: list<int>}
     */
    public function generate(): array
    {
        $row = range(0, 9);
        $col = range(0, 9);

        shuffle($row);
        shuffle($col);

        return [
            'row' => $row,
            'col' => $col,
        ];
    }

    /**
     * Generate only row numbers.
     *
     * @return list<int>
     */
    public function generateRow(): array
    {
        $row = range(0, 9);
        shuffle($row);

        return $row;
    }

    /**
     * Generate only column numbers.
     *
     * @return list<int>
     */
    public function generateColumn(): array
    {
        $col = range(0, 9);
        shuffle($col);

        return $col;
    }
}
