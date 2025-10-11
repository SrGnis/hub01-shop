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
        Schema::table('project_version', function (Blueprint $table) {
            // Add a unique constraint to ensure version is unique within a project
            $table->unique(['project_id', 'version'], 'project_version_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_version', function (Blueprint $table) {
            $table->dropUnique('project_version_unique');
        });
    }
};
