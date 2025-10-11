<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->unique('slug');
            $table->index('slug');
        });

        // Generate slugs for existing projects
        $projects = \App\Models\Project::all();
        foreach ($projects as $project) {
            $project->slug = \Illuminate\Support\Str::slug($project->name);

            // Handle duplicate slugs by appending a random string
            $originalSlug = $project->slug;
            $counter = 1;

            while (\App\Models\Project::where('slug', $project->slug)->where('id', '!=', $project->id)->exists()) {
                $project->slug = $originalSlug.'-'.$counter++;
            }

            $project->save();
        }

        // Make slug required after populating existing records
        Schema::table('project', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
