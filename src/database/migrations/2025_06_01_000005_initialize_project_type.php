<?php

use App\Models\ProjectType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (ProjectType::count() === 0) {
            ProjectType::create([
                'value' => 'mod',
                'display_name' => 'Mod',
                'icon' => 'lucide-puzzle',
            ]);
            ProjectType::create([
                'value' => 'tile_set',
                'display_name' => 'Tile Set',
                'icon' => 'lucide-grid',
            ]);
            ProjectType::create([
                'value' => 'sound_pack',
                'display_name' => 'Sound Pack',
                'icon' => 'lucide-volume-2',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
