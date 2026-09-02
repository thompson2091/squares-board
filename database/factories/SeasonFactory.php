<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Season>
     */
    protected $model = Season::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'slug' => null,
            'owner_id' => User::factory(),
            'name' => fake()->sentence(3).' Season Pool',
            'description' => fake()->optional()->paragraph(),
            'total_weeks' => 18,
            'number_draw_mode' => Season::DRAW_UPFRONT,
            'status' => Season::STATUS_OPEN,
        ];
    }

    /**
     * Numbers are drawn week by week rather than all at the start.
     */
    public function manualDraw(): static
    {
        return $this->state(fn (array $attributes): array => [
            'number_draw_mode' => Season::DRAW_MANUAL,
        ]);
    }

    /**
     * The season is under way.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Season::STATUS_ACTIVE,
        ]);
    }

    /**
     * A shorter season, to keep tests quick.
     */
    public function weeks(int $weeks): static
    {
        return $this->state(fn (array $attributes): array => [
            'total_weeks' => $weeks,
        ]);
    }
}
