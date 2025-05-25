<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectVersionDependencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Process project versions in chunks to avoid memory issues
        ProjectVersion::query()->chunkById(100, function ($projectVersions) {
            $dependencyRecords = [];
            $projectIds = $projectVersions->pluck('project_id', 'id')->toArray();

            // Get a sample of potential dependency versions for efficiency
            $potentialDependencyVersions = ProjectVersion::whereNotIn('project_id', array_values($projectIds))
                ->inRandomOrder()
                ->limit(50)
                ->get(['id', 'project_id'])
                ->keyBy('id');

            // Get a sample of potential dependency projects for efficiency
            $potentialDependencyProjects = Project::whereNotIn('id', array_values($projectIds))
                ->inRandomOrder()
                ->limit(30)
                ->get(['id'])
                ->keyBy('id');

            foreach ($projectVersions as $projectVersion) {
                // Skip some versions to avoid creating dependencies for every version
                if (fake()->boolean(30)) {
                    continue;
                }

                // Create 0-3 specific version dependencies
                $specificVersionCount = fake()->numberBetween(0, 2);
                if ($specificVersionCount > 0 && $potentialDependencyVersions->count() > 0) {
                    // Select random dependency versions
                    $specificVersionDependencies = $potentialDependencyVersions->random(
                        min($specificVersionCount, $potentialDependencyVersions->count())
                    );

                    foreach ($specificVersionDependencies as $dependencyVersion) {
                        $dependencyRecords[] = [
                            'project_version_id' => $projectVersion->id,
                            'dependency_project_version_id' => $dependencyVersion->id,
                            'dependency_project_id' => null,
                            'dependency_type' => fake()->randomElement(['required', 'optional', 'embedded']),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Create 0-2 general project dependencies
                $generalProjectCount = fake()->numberBetween(0, 1);
                if ($generalProjectCount > 0 && $potentialDependencyProjects->count() > 0) {
                    // Select random dependency projects
                    $generalProjectDependencies = $potentialDependencyProjects->random(
                        min($generalProjectCount, $potentialDependencyProjects->count())
                    );

                    foreach ($generalProjectDependencies as $dependencyProject) {
                        $dependencyRecords[] = [
                            'project_version_id' => $projectVersion->id,
                            'dependency_project_version_id' => null,
                            'dependency_project_id' => $dependencyProject->id,
                            'dependency_type' => fake()->randomElement(['required', 'optional', 'embedded']),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Bulk insert all dependency records for this chunk
            if (! empty($dependencyRecords)) {
                DB::table('project_version_dependency')->insert($dependencyRecords);
            }
        });
    }
}
