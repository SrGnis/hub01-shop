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
}
