<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectTag>
 */
class ProjectTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'icon' => 'lucide-tag',
            'display_priority' => 0,
            'project_tag_group_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
