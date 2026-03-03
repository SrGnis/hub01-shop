<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDailyDownload;
use App\Models\ProjectVersionTag;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectVersion>
 */
class ProjectVersionFactory extends Factory
{
    protected bool $generateDailyDownloads = true;

    protected int $dailyDownloadsMaxDays = 180;

    protected int $dailyDownloadsMin = 2;

    protected int $dailyDownloadsMax = 120;

    protected bool $dailyDownloadsDeterministic = false;

    protected float $dailyDownloadsGrowthExponent = 1.4;

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

            if ($this->generateDailyDownloads) {
                $this->generateTrendingDailyDownloads($projectVersion);
            }
        });

    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $releaseDate = fake()->dateTimeBetween('-1 years', 'now');

        return [
            'name' => fake()->words(rand(1, 3), true),
            'version' => fake()->unique()->numerify('#.#.#'),
            'changelog' => fake()->paragraphs(rand(1, 3), true),
            'release_type' => fake()->randomElement(['alpha', 'beta', 'prerelease', 'rc', 'release']),
            'release_date' => $releaseDate,
            'project_id' => Project::factory(),
            'created_at' => $releaseDate,
            'updated_at' => $releaseDate,
        ];
    }

    /**
     * Disable automatic daily download history generation.
     */
    public function withoutDailyDownloads(): static
    {
        return $this
            ->withDailyDownloadConfiguration([
                'generateDailyDownloads' => false,
            ])
            ->afterCreating(function (ProjectVersion $projectVersion) {
                $projectVersion->dailyDownloads()->delete();
            });
    }

    /**
     * Generate stronger recent-growth traffic.
     */
    public function highIntensityDailyDownloads(): static
    {
        return $this->withDailyDownloadConfiguration([
            'dailyDownloadsMin' => 20,
            'dailyDownloadsMax' => 300,
            'dailyDownloadsGrowthExponent' => 1.7,
        ]);
    }

    /**
     * Restrict generated history to a max day window.
     */
    public function dailyDownloadWindow(int $maxDays): static
    {
        return $this->withDailyDownloadConfiguration([
            'dailyDownloadsMaxDays' => max(1, $maxDays),
        ]);
    }

    /**
     * Use deterministic generation for stable test assertions.
     */
    public function deterministicDailyDownloads(): static
    {
        return $this->withDailyDownloadConfiguration([
            'dailyDownloadsDeterministic' => true,
        ]);
    }

    /**
     * Override the daily download min/max range.
     */
    public function dailyDownloadRange(int $min, int $max): static
    {
        $normalizedMin = max(0, min($min, $max));
        $normalizedMax = max($normalizedMin, max($min, $max));

        return $this->withDailyDownloadConfiguration([
            'dailyDownloadsMin' => $normalizedMin,
            'dailyDownloadsMax' => $normalizedMax,
        ]);
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

    /**
     * @param  array<string, bool|int|float>  $overrides
     */
    protected function withDailyDownloadConfiguration(array $overrides): static
    {
        $factory = clone $this;

        foreach ($overrides as $property => $value) {
            $factory->{$property} = $value;
        }

        return $factory;
    }

    protected function generateTrendingDailyDownloads(ProjectVersion $projectVersion): void
    {
        $releaseDate = CarbonImmutable::parse($projectVersion->release_date ?? $projectVersion->created_at)->startOfDay();
        $today = CarbonImmutable::today();

        if ($releaseDate->greaterThan($today)) {
            $releaseDate = $today;
        }

        $totalDaysInclusive = $releaseDate->diffInDays($today) + 1;
        $windowDays = min($totalDaysInclusive, $this->dailyDownloadsMaxDays);
        $historyStart = $today->subDays($windowDays - 1);

        if ($releaseDate->greaterThan($historyStart)) {
            $historyStart = $releaseDate;
        }

        $historyDays = (int) $historyStart->diffInDays($today);
        $historySpan = max(1, $historyDays);
        $rows = [];

        for ($offset = 0; $offset <= $historyDays; $offset++) {
            $date = $historyStart->addDays($offset);
            $progress = $historyDays <= 0 ? 1.0 : $offset / $historySpan;
            $trendProgress = $progress ** $this->dailyDownloadsGrowthExponent;

            $base = $this->dailyDownloadsMin + (($this->dailyDownloadsMax - $this->dailyDownloadsMin) * $trendProgress);
            $variance = max(1, (int) round($base * 0.2));

            if ($this->dailyDownloadsDeterministic) {
                $seed = crc32($projectVersion->id.'|'.$date->toDateString());
                $noise = ($seed % (($variance * 2) + 1)) - $variance;
            } else {
                $noise = random_int(-$variance, $variance);
            }

            $downloads = max(0, (int) round($base + $noise));

            $rows[] = [
                'project_version_id' => $projectVersion->id,
                'date' => $date->toDateString(),
                'downloads' => $downloads,
                'created_at' => $projectVersion->created_at,
                'updated_at' => $projectVersion->updated_at,
            ];
        }

        ProjectVersionDailyDownload::query()->insert($rows);
    }
}
