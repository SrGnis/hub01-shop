<?php

namespace Database\Factories;

use App\Models\ProjectType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectCategory>
 */
class ProjectCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $projectType = ProjectType::inRandomOrder()->first();

        return [
            'name' => fake()->unique()->word(),
            'icon' => 'lucide-tag',
            'project_type_id' => $projectType->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
