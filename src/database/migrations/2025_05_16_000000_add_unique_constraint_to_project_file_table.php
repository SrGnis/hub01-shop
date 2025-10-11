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
        Schema::table('project_file', function (Blueprint $table) {
            // Add a unique constraint to ensure file name is unique within a version
            $table->unique(['project_version_id', 'name'], 'project_file_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_file', function (Blueprint $table) {
            $table->dropUnique('project_file_unique');
        });
    }
};
