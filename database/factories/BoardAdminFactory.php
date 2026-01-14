<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Board;
use App\Models\BoardAdmin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardAdmin>
 */
class BoardAdminFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<BoardAdmin>
     */
    protected $model = BoardAdmin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'user_id' => User::factory(),
            'role' => BoardAdmin::ROLE_ADMIN,
        ];
    }

    /**
     * Indicate that this is an admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => BoardAdmin::ROLE_ADMIN,
        ]);
    }

    /**
     * Indicate that this is a viewer role.
     */
    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => BoardAdmin::ROLE_VIEWER,
        ]);
    }
}
