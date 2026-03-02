<?php

namespace Database\Factories;

use App\Models\ProjectVersion;
use App\Models\ProjectVersionDailyDownload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectVersionDailyDownload>
 */
class ProjectVersionDailyDownloadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ProjectVersionDailyDownload::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_version_id' => ProjectVersion::factory(),
            'date' => now()->toDateString(),
            'downloads' => fake()->numberBetween(1, 75),
        ];
    }

    /**
     * Target a specific project version.
     */
    public function forVersion(ProjectVersion $projectVersion): static
    {
        return $this->state(fn () => [
            'project_version_id' => $projectVersion->id,
        ]);
    }

    /**
     * Set an explicit date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn () => [
            'date' => $date,
        ]);
    }

    /**
     * Set an explicit download total.
     */
    public function withDownloads(int $downloads): static
    {
        return $this->state(fn () => [
            'downloads' => max(0, $downloads),
        ]);
    }

    /**
     * Lower traffic profile.
     */
    public function lowVolume(): static
    {
        return $this->state(fn () => [
            'downloads' => fake()->numberBetween(1, 30),
        ]);
    }

    /**
     * Higher traffic profile.
     */
    public function highVolume(): static
    {
        return $this->state(fn () => [
            'downloads' => fake()->numberBetween(250, 1500),
        ]);
    }
}
