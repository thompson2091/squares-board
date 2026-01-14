<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Board;
use App\Models\Square;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Square>
 */
class SquareFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Square>
     */
    protected $model = Square::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'user_id' => null,
            'row' => fake()->numberBetween(0, 9),
            'col' => fake()->numberBetween(0, 9),
            'is_paid' => false,
            'claimed_at' => null,
            'paid_at' => null,
        ];
    }

    /**
     * Indicate that the square has been claimed.
     */
    public function claimed(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user !== null ? $user->id : User::factory(),
            'claimed_at' => now(),
        ]);
    }

    /**
     * Indicate that the square has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => true,
            'paid_at' => now(),
        ]);
    }

    /**
     * Set specific row and column.
     */
    public function position(int $row, int $col): static
    {
        return $this->state(fn (array $attributes) => [
            'row' => $row,
            'col' => $col,
        ]);
    }
}
