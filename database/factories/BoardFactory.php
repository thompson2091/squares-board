<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Board>
     */
    protected $model = Board::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'owner_id' => User::factory(),
            'name' => fake()->sentence(3).' Super Bowl Pool',
            'description' => fake()->optional()->paragraph(),
            'team_row' => fake()->randomElement(['Chiefs', 'Bills', 'Ravens', 'Bengals', 'Dolphins', 'Steelers']),
            'team_col' => fake()->randomElement(['49ers', 'Eagles', 'Cowboys', 'Lions', 'Packers', 'Seahawks']),
            'game_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'price_per_square' => fake()->randomElement([500, 1000, 2000, 2500, 5000]), // $5-$50 in cents
            'max_squares_per_user' => fake()->numberBetween(1, 10),
            'is_public' => fake()->boolean(70),
            'status' => Board::STATUS_DRAFT,
            'row_numbers' => null,
            'col_numbers' => null,
            'numbers_revealed' => false,
            'payment_instructions' => fake()->optional()->text(200),
        ];
    }

    /**
     * Indicate that the board is open for claiming.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Board::STATUS_OPEN,
        ]);
    }

    /**
     * Indicate that the board is locked.
     */
    public function locked(): static
    {
        $rowNumbers = range(0, 9);
        $colNumbers = range(0, 9);
        shuffle($rowNumbers);
        shuffle($colNumbers);

        return $this->state(fn (array $attributes) => [
            'status' => Board::STATUS_LOCKED,
            'row_numbers' => $rowNumbers,
            'col_numbers' => $colNumbers,
            'numbers_revealed' => true,
        ]);
    }

    /**
     * Indicate that the board is completed.
     */
    public function completed(): static
    {
        $rowNumbers = range(0, 9);
        $colNumbers = range(0, 9);
        shuffle($rowNumbers);
        shuffle($colNumbers);

        return $this->state(fn (array $attributes) => [
            'status' => Board::STATUS_COMPLETED,
            'row_numbers' => $rowNumbers,
            'col_numbers' => $colNumbers,
            'numbers_revealed' => true,
        ]);
    }

    /**
     * Indicate that the board is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the board is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
