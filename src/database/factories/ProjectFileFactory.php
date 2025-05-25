<?php

namespace Database\Factories;

use App\Models\ProjectVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectFile>
 */
class ProjectFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(rand(1, 3), true);
        $fileName = str_replace(' ', '_', $name).'.zip';
        $file = UploadedFile::fake()->create($fileName, fake()->numberBetween(10, 100));
        $filePath = $file->store('project-files');

        return [
            'name' => $fileName,
            'path' => $filePath,
            'size' => $file->getSize(),
            'project_version_id' => ProjectVersion::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
