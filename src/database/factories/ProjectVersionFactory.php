<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectVersion>
 */
class ProjectVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(rand(1, 3), true),
            'version' => fake()->unique()->numerify('#.#.#'),
            'changelog' => fake()->paragraphs(rand(1, 3), true),
            'release_type' => fake()->randomElement(['alpha', 'beta', 'release']),
            'release_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'downloads' => fake()->numberBetween(0, 10000),
            'project_id' => Project::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the project version is an alpha release.
     */
    public function alpha(): static
    {
        return $this->state(fn (array $attributes) => [
            'release_type' => 'alpha',
        ]);
    }

    /**
     * Indicate that the project version is a beta release.
     */
    public function beta(): static
    {
        return $this->state(fn (array $attributes) => [
            'release_type' => 'beta',
        ]);
    }

    /**
     * Indicate that the project version is a stable release.
     */
    public function stable(): static
    {
        return $this->state(fn (array $attributes) => [
            'release_type' => 'release',
        ]);
    }
}
