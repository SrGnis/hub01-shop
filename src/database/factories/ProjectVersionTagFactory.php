<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectVersionTag>
 */
class ProjectVersionTagFactory extends Factory
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
            'project_version_tag_group_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
