<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::beginTransaction();

        // Create fewer users for faster seeding
        User::factory(20)->create();

        // Create project types
        $projectTypes = $this->createProjectTypes();

        // Create tag groups and tags
        $tagGroupIds = $this->createTagGroups();
        $this->createTags($tagGroupIds, $projectTypes);

        // Create projects with fewer records for faster seeding
        $this->createProjects();

        // Call other seeders
        $this->call([
            MembershipSeeder::class,
            ProjectVersionDependencySeeder::class,
            ProjectVersionTagSeeder::class,
        ]);

        // Assign random version tags to project versions
        $this->assignRandomTagsToProjectVersions();

        DB::commit();
    }

    /**
     * Create project types
     */
    private function createProjectTypes(): Collection
    {
        $projectTypeData = [
            [
                'value' => 'mod',
                'display_name' => 'Mod',
                'icon' => 'lucide-puzzle',
            ],
            [
                'value' => 'tile_set',
                'display_name' => 'Tile Set',
                'icon' => 'lucide-grid',
            ],
            [
                'value' => 'sound_pack',
                'display_name' => 'Sound Pack',
                'icon' => 'lucide-volume-2',
            ],
        ];

        $now = now();
        $projectTypesToInsert = [];

        foreach ($projectTypeData as $type) {
            // Check if project type already exists
            $exists = DB::table('project_type')->where('value', $type['value'])->exists();

            if (! $exists) {
                $projectTypesToInsert[] = [
                    'value' => $type['value'],
                    'display_name' => $type['display_name'],
                    'icon' => $type['icon'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($projectTypesToInsert)) {
            DB::table('project_type')->insert($projectTypesToInsert);
        }

        return DB::table('project_type')->get();
    }

    /**
     * Create tag groups
     */
    private function createTagGroups(): array
    {
        $tagGroupData = [
            ['name' => 'Content Type'],
            ['name' => 'Gameplay Features'],
            ['name' => 'Art Style'],
            ['name' => 'Sound Type'],
            ['name' => 'Theme'],
        ];

        $now = now();
        $tagGroupsToInsert = [];

        foreach ($tagGroupData as $group) {
            $tagGroupsToInsert[] = [
                'name' => $group['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('project_tag_group')->insert($tagGroupsToInsert);

        // Get the inserted IDs
        $tagGroups = DB::table('project_tag_group')
            ->whereIn('name', array_column($tagGroupData, 'name'))
            ->pluck('id', 'name')
            ->toArray();

        return $tagGroups;
    }

    /**
     * Create tags and associate them with project types
     */
    private function createTags(array $tagGroups, Collection $projectTypes): void
    {
        $projectTypesByValue = $projectTypes->keyBy('value');

        // Define tags with their associations
        $tagsData = [
            // Content Type tags
            ['name' => 'Equipment', 'icon' => 'lucide-swords', 'group' => 'Content Type', 'project_types' => ['mod']],
            ['name' => 'Creatures', 'icon' => 'lucide-paw-print', 'group' => 'Content Type', 'project_types' => ['mod']],
            ['name' => 'Vehicles', 'icon' => 'lucide-car', 'group' => 'Content Type', 'project_types' => ['mod']],

            // Gameplay Features tags
            ['name' => 'Magic', 'icon' => 'lucide-wand-sparkles', 'group' => 'Gameplay Features', 'project_types' => ['mod']],
            ['name' => 'Game Mechanics', 'icon' => 'lucide-sliders-horizontal', 'group' => 'Gameplay Features', 'project_types' => ['mod']],

            // Art Style tags
            ['name' => 'Pixel Art', 'icon' => 'lucide-grid', 'group' => 'Art Style', 'project_types' => ['tile_set']],
            ['name' => 'Realistic', 'icon' => 'lucide-image', 'group' => 'Art Style', 'project_types' => ['tile_set']],

            // Sound Type tags
            ['name' => 'Ambient', 'icon' => 'lucide-wind', 'group' => 'Sound Type', 'project_types' => ['sound_pack']],
            ['name' => 'Music', 'icon' => 'lucide-music', 'group' => 'Sound Type', 'project_types' => ['sound_pack']],

            // Theme tags (for all project types)
            ['name' => 'Sci-Fi', 'icon' => 'lucide-rocket', 'group' => 'Theme', 'project_types' => ['mod', 'tile_set', 'sound_pack']],
            ['name' => 'Fantasy', 'icon' => 'lucide-wand', 'group' => 'Theme', 'project_types' => ['mod', 'tile_set', 'sound_pack']],
        ];

        $now = now();
        $tagsToInsert = [];
        $tagProjectTypeRelations = [];
        $tagGroupProjectTypeRelations = [];
        $processedTagGroups = [];

        foreach ($tagsData as $tagData) {
            // Create tag
            $tag = [
                'name' => $tagData['name'],
                'icon' => $tagData['icon'],
                'project_tag_group_id' => $tagGroups[$tagData['group']],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $tagsToInsert[] = $tag;
        }

        // Bulk insert tags
        DB::table('project_tag')->insert($tagsToInsert);

        // Get inserted tags
        $insertedTags = DB::table('project_tag')
            ->whereIn('name', array_column($tagsData, 'name'))
            ->get(['id', 'name', 'project_tag_group_id']);

        // Prepare tag-project type relations
        foreach ($tagsData as $tagData) {
            $tag = $insertedTags->where('name', $tagData['name'])->first();
            $groupId = $tagGroups[$tagData['group']];

            foreach ($tagData['project_types'] as $projectTypeValue) {
                if (isset($projectTypesByValue[$projectTypeValue])) {
                    $projectTypeId = $projectTypesByValue[$projectTypeValue]->id;

                    // Tag to project type relation
                    $tagProjectTypeRelations[] = [
                        'project_type_id' => $projectTypeId,
                        'tag_id' => $tag->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Tag group to project type relation (if not already processed)
                    $groupKey = $groupId.'-'.$projectTypeId;
                    if (! isset($processedTagGroups[$groupKey])) {
                        $tagGroupProjectTypeRelations[] = [
                            'project_type_id' => $projectTypeId,
                            'tag_group_id' => $groupId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $processedTagGroups[$groupKey] = true;
                    }
                }
            }
        }

        // Bulk insert relations
        if (! empty($tagProjectTypeRelations)) {
            DB::table('project_tag_project_type')->insert($tagProjectTypeRelations);
        }

        if (! empty($tagGroupProjectTypeRelations)) {
            DB::table('project_tag_group_project_type')->insert($tagGroupProjectTypeRelations);
        }
    }

    /**
     * Create projects with versions and files
     */
    private function createProjects(): void
    {
        // Create fewer projects for faster seeding
        Project::factory(30)
            ->mod()
            ->has(
                ProjectVersion::factory(2)
                    ->has(
                        ProjectFile::factory(1),
                        'files'
                    ),
                'versions'
            )
            ->create();

        Project::factory(5)
            ->tileSet()
            ->has(
                ProjectVersion::factory(50)
                    ->has(
                        ProjectFile::factory(1),
                        'files'
                    ),
                'versions'
            )
            ->create();

        Project::factory(5)
            ->soundPack()
            ->has(
                ProjectVersion::factory(2)
                    ->has(
                        ProjectFile::factory(1),
                        'files'
                    ),
                'versions'
            )
            ->create();
    }

    /**
     * Assign random version tags to project versions based on their project type
     */
    private function assignRandomTagsToProjectVersions(): void
    {
        // Process project versions in chunks to avoid memory issues
        ProjectVersion::with('project.projectType')->chunkById(50, function ($projectVersions) {
            $tagAssignments = [];
            $now = now();

            // Get all project types in this chunk
            $projectTypeIds = $projectVersions->pluck('project.project_type_id')->unique()->toArray();

            // Get tags for these project types
            $tagsByProjectType = [];
            foreach ($projectTypeIds as $projectTypeId) {
                $tagsByProjectType[$projectTypeId] = ProjectVersionTag::whereHas('projectTypes', function ($query) use ($projectTypeId) {
                    $query->where('project_type_id', $projectTypeId);
                })->pluck('id')->toArray();
            }

            foreach ($projectVersions as $version) {
                $projectTypeId = $version->project->project_type_id;

                // Skip if no tags for this project type
                if (empty($tagsByProjectType[$projectTypeId])) {
                    continue;
                }

                // Select 1-2 random tags
                $tagCount = min(rand(1, 2), count($tagsByProjectType[$projectTypeId]));
                $randomTagKeys = array_rand($tagsByProjectType[$projectTypeId], $tagCount);

                // Convert to array if only one tag selected
                if (! is_array($randomTagKeys)) {
                    $randomTagKeys = [$randomTagKeys];
                }

                foreach ($randomTagKeys as $key) {
                    $tagId = $tagsByProjectType[$projectTypeId][$key];
                    $tagAssignments[] = [
                        'project_version_id' => $version->id,
                        'tag_id' => $tagId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Bulk insert all tag assignments
            if (! empty($tagAssignments)) {
                DB::table('project_version_project_version_tag')->insert($tagAssignments);
            }

            // Clear caches in bulk
            foreach ($projectVersions as $version) {
                $version->clearTagsCache();
            }
        });
    }
}
