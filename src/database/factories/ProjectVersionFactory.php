<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionTag;
use App\Models\ProjectType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectVersion>
 */
class ProjectVersionFactory extends Factory
{
    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (ProjectVersion $projectVersion) {
            // Assign tags and subtags to the project version
            $projectType = $projectVersion->project->projectType;

            // Get tags that belong to the project's project type
            $tags = ProjectVersionTag::whereHas('projectTypes', function ($query) use ($projectType) {
                $query->where('project_type_id', $projectType->id);
            })->whereNull('parent_id')->inRandomOrder()->take(rand(1, 2))->get();

            $projectVersion->tags()->attach($tags);

            // Assign some random subtags from the already assigned tags
            $subTags = ProjectVersionTag::whereIn('parent_id', $tags->pluck('id'))->inRandomOrder()->take(rand(0, 2))->get();
            $projectVersion->tags()->attach($subTags);
        });

    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $relese_date = fake()->dateTimeBetween('-1 years', 'now');
        return [
            'name' => fake()->words(rand(1, 3), true),
            'version' => fake()->unique()->numerify('#.#.#'),
            'changelog' => fake()->paragraphs(rand(1, 3), true),
            'release_type' => fake()->randomElement(['alpha', 'beta', 'prerelease', 'rc','release']),
            'release_date' => $relese_date,
            'downloads' => fake()->numberBetween(0, 10000),
            'project_id' => Project::factory(),
            'created_at' => $relese_date,
            'updated_at' => $relese_date,
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
     * Indicate that the project version is a pre-release.
     */
    public function prerelease(): static
    {
        return $this->state(fn (array $attributes) => [
            'release_type' => 'prerelease',
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
