<?php

namespace Database\Seeders;

use App\Models\ProjectType;
use App\Models\ProjectVersionTag;
use App\Models\ProjectVersionTagGroup;
use Illuminate\Database\Seeder;

class ProjectVersionTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all project types
        $projectTypes = ProjectType::all();

        // Create Game Compatibility tag group
        $compatibilityGroup = ProjectVersionTagGroup::create([
            'name' => 'Game Compatibility',
        ]);
        $compatibilityGroup->projectTypes()->attach($projectTypes->pluck('id'));

        // Create tags for Game Compatibility group
        $compatibilityTags = [
            ['name' => 'Dark Days Ahead', 'icon' => 'lucide-biohazard'],
            ['name' => 'Bright Nights', 'icon' => 'lucide-moon'],
            ['name' => 'The Last Generation', 'icon' => 'lucide-tree-deciduous'],
            ['name' => 'There Is Still Hope', 'icon' => 'lucide-sun'],
        ];

        foreach ($compatibilityTags as $tagData) {
            $tag = ProjectVersionTag::create([
                'name' => $tagData['name'],
                'icon' => $tagData['icon'],
                'project_version_tag_group_id' => $compatibilityGroup->id,
            ]);
            $tag->projectTypes()->attach($projectTypes->pluck('id'));

            // Create game version subtags for this game
            $this->createGameVersionSubtags($tag);
        }

        // Create Resolution tag group
        $resolutionGroup = ProjectVersionTagGroup::create([
            'name' => 'Resolution',
        ]);
        $resolutionGroup->projectTypes()->attach(ProjectType::where('value', 'tile_set')->first()->id);

        // Create tags for Resolution group
        $resolutionTags = [
            ['name' => '16x', 'icon' => 'lucide-square'],
            ['name' => '32x', 'icon' => 'lucide-square'],
            ['name' => '64x', 'icon' => 'lucide-square'],
            ['name' => '128x', 'icon' => 'lucide-square'],
            ['name' => '256x', 'icon' => 'lucide-square'],
            ['name' => '512x', 'icon' => 'lucide-square'],
        ];

        foreach ($resolutionTags as $tagData) {
            $tag = ProjectVersionTag::create([
                'name' => $tagData['name'],
                'icon' => $tagData['icon'],
                'project_version_tag_group_id' => $resolutionGroup->id,
            ]);
            $tag->projectTypes()->attach(ProjectType::where('value', 'tile_set')->first()->id);
        }

    }

    /**
     * Create game version subtags for a parent tag
     */
    private function createGameVersionSubtags(ProjectVersionTag $parentTag): void
    {
        $gameVersions = [
            ['name' => '0.G', 'icon' => 'lucide-tag'],
            ['name' => '1.0', 'icon' => 'lucide-tag'],
            ['name' => '2.0', 'icon' => 'lucide-tag'],
            ['name' => '3.0', 'icon' => 'lucide-tag'],
            ['name' => '4.0', 'icon' => 'lucide-tag'],
            ['name' => '5.0', 'icon' => 'lucide-tag'],
            ['name' => '6.0', 'icon' => 'lucide-tag'],
            ['name' => '7.0', 'icon' => 'lucide-tag'],
            ['name' => '8.0', 'icon' => 'lucide-tag'],
            ['name' => '9.0', 'icon' => 'lucide-tag'],
            ['name' => '10.0', 'icon' => 'lucide-tag'],
        ];

        foreach ($gameVersions as $versionData) {
            ProjectVersionTag::create([
                'name' => $versionData['name'],
                'icon' => $versionData['icon'],
                'parent_id' => $parentTag->id,
            ]);
        }
    }
}
