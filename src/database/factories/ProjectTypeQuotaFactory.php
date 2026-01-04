<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectTypeQuota>
 */
class ProjectTypeQuotaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_storage_max' => 1073741824, // 1GB in bytes
            'versions_per_day_max' => 10,
            // version_size_max, files_per_version_max, and file_size_max intentionally not set
            // to allow config defaults to apply when not overridden
        ];
    }

    /**
     * Set a specific project storage limit.
     */
    public function storageLimit(int $bytes): static
    {
        return $this->state(function () use ($bytes) {
            return [
                'project_storage_max' => $bytes,
            ];
        });
    }

    /**
     * Set versions per day limit.
     */
    public function versionsPerDay(int $count): static
    {
        return $this->state(function () use ($count) {
            return [
                'versions_per_day_max' => $count,
            ];
        });
    }

    /**
     * Set version size limit.
     */
    public function versionSize(int $bytes): static
    {
        return $this->state(function () use ($bytes) {
            return [
                'version_size_max' => $bytes,
            ];
        });
    }

    /**
     * Set files per version limit.
     */
    public function filesPerVersion(int $count): static
    {
        return $this->state(function () use ($count) {
            return [
                'files_per_version_max' => $count,
            ];
        });
    }

    /**
     * Set file size limit.
     */
    public function fileSize(int $bytes): static
    {
        return $this->state(function () use ($bytes) {
            return [
                'file_size_max' => $bytes,
            ];
        });
    }
}
