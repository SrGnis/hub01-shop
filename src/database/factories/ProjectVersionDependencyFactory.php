<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectVersionDependency>
 */
class ProjectVersionDependencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Randomly choose between specific version dependency or general project dependency
        $useSpecificVersion = fake()->boolean();

        return [
            'project_version_id' => ProjectVersion::factory(),
            'dependency_project_version_id' => $useSpecificVersion ? ProjectVersion::factory() : null,
            'dependency_project_id' => $useSpecificVersion ? null : Project::factory(),
            'dependency_type' => fake()->randomElement(['required', 'optional', 'embedded']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the dependency is required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'dependency_type' => 'required',
        ]);
    }

    /**
     * Indicate that the dependency is optional.
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'dependency_type' => 'optional',
        ]);
    }

    /**
     * Indicate that the dependency is embedded.
     */
    public function embedded(): static
    {
        return $this->state(fn (array $attributes) => [
            'dependency_type' => 'embedded',
        ]);
    }

    /**
     * Indicate that the dependency is for a specific version.
     */
    public function specificVersion(): static
    {
        return $this->state(fn (array $attributes) => [
            'dependency_project_version_id' => ProjectVersion::factory(),
            'dependency_project_id' => null,
        ]);
    }

    /**
     * Indicate that the dependency is for a general project.
     */
    public function generalProject(): static
    {
        return $this->state(fn (array $attributes) => [
            'dependency_project_version_id' => null,
            'dependency_project_id' => Project::factory(),
        ]);
    }
}
