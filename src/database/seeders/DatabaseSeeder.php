<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectTag;
use App\Models\ProjectTagGroup;
use App\Models\ProjectVersion;
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

        // Create project version tag groups and tags
        $this->call(ProjectVersionTagSeeder::class);

        // Create projects with fewer records for faster seeding
        $this->createProjects();

        // Call other seeders
        $this->call([
            MembershipSeeder::class,
            ProjectVersionDependencySeeder::class,
        ]);

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

        $tagGroupsToInsert = [];

        foreach ($tagGroupData as $group) {
            ProjectTagGroup::create([
                'name' => $group['name'],
            ]);
        }

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
            ];

            ProjectTag::create($tag);
        }

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

        // Create subtags (no tag group or project type needed)
        $this->createSubTags($insertedTags);
    }

    /**
     * Create subtags for existing tags
     */
    private function createSubTags(Collection $insertedTags): void
    {
        $subTagsData = [
            // Equipment subtags
            'Equipment' => [
                ['name' => 'Weapons', 'icon' => 'lucide-sword'],
                ['name' => 'Armor', 'icon' => 'lucide-shield'],
                ['name' => 'Accessories', 'icon' => 'lucide-gem'],
                ['name' => 'Tools', 'icon' => 'lucide-hammer'],
            ],
            // Creatures subtags
            'Creatures' => [
                ['name' => 'Hostile', 'icon' => 'lucide-skull'],
                ['name' => 'Friendly', 'icon' => 'lucide-heart'],
                ['name' => 'Neutral', 'icon' => 'lucide-meh'],
            ],
            // Magic subtags
            'Magic' => [
                ['name' => 'Spells', 'icon' => 'lucide-sparkles'],
                ['name' => 'Enchantments', 'icon' => 'lucide-star'],
                ['name' => 'Potions', 'icon' => 'lucide-flask-conical'],
            ],
            // Pixel Art subtags
            'Pixel Art' => [
                ['name' => 'Top-down', 'icon' => 'lucide-view'],
                ['name' => 'Isometric', 'icon' => 'lucide-box'],
                ['name' => 'Animated', 'icon' => 'lucide-move'],
            ],
            // Realistic subtags
            'Realistic' => [
                ['name' => 'Photorealistic', 'icon' => 'lucide-camera'],
                ['name' => 'Hand-painted', 'icon' => 'lucide-palette'],
            ],
            // Ambient subtags
            'Ambient' => [
                ['name' => 'Environment', 'icon' => 'lucide-tree-pine'],
                ['name' => 'Weather', 'icon' => 'lucide-cloud-rain'],
                ['name' => 'Interior', 'icon' => 'lucide-home'],
            ],
            // Music subtags
            'Music' => [
                ['name' => 'OST', 'icon' => 'lucide-music-2'],
                ['name' => 'Battle Themes', 'icon' => 'lucide-zap'],
                ['name' => 'Exploration', 'icon' => 'lucide-compass'],
            ],
            // Sci-Fi subtags
            'Sci-Fi' => [
                ['name' => 'Space', 'icon' => 'lucide-rocket'],
                ['name' => 'Cyberpunk', 'icon' => 'lucide-cpu'],
                ['name' => 'Post-Apocalyptic', 'icon' => 'lucide-radiation'],
            ],
            // Fantasy subtags
            'Fantasy' => [
                ['name' => 'Medieval', 'icon' => 'lucide-castle'],
                ['name' => 'Mythology', 'icon' => 'lucide-dragon'],
                ['name' => 'Horror', 'icon' => 'lucide-ghost'],
            ],
        ];

        foreach ($subTagsData as $parentName => $subTags) {
            $parentTag = $insertedTags->where('name', $parentName)->first();

            if ($parentTag) {
                foreach ($subTags as $subTagData) {
                    $subTagData['parent_id'] = $parentTag->id;

                    ProjectTag::create($subTagData);
                }
            }
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
                        ProjectFile::factory(5),
                        'files'
                    ),
                'versions'
            )
            ->create();
    }
}
