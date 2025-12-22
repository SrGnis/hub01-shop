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
        // Create project_type_quotas table
        Schema::create('project_type_quota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_type_id')->constrained('project_type')->onDelete('cascade');
            $table->unsignedInteger('pending_projects_max')->nullable();
            $table->unsignedBigInteger('total_storage_max')->nullable();
            $table->unsignedBigInteger('project_storage_max')->nullable();
            $table->unsignedInteger('versions_per_day_max')->nullable();
            $table->unsignedBigInteger('version_size_max')->nullable();
            $table->unsignedInteger('files_per_version_max')->nullable();
            $table->unsignedBigInteger('file_size_max')->nullable();
            $table->timestamps();
            
            // One quota record per project type
            $table->unique('project_type_id');
        });

        // Create project_quotas table
        Schema::create('project_quota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('project')->onDelete('cascade');
            $table->unsignedInteger('pending_projects_max')->nullable();
            $table->unsignedBigInteger('total_storage_max')->nullable();
            $table->unsignedBigInteger('project_storage_max')->nullable();
            $table->unsignedInteger('versions_per_day_max')->nullable();
            $table->unsignedBigInteger('version_size_max')->nullable();
            $table->unsignedInteger('files_per_version_max')->nullable();
            $table->unsignedBigInteger('file_size_max')->nullable();
            $table->timestamps();
            
            // One quota record per project
            $table->unique('project_id');
        });

        // Drop the old JSON columns
        Schema::table('project_type', function (Blueprint $table) {
            $table->dropColumn('quota_overrides');
        });

        Schema::table('project', function (Blueprint $table) {
            $table->dropColumn('quota_overrides');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back JSON columns
        Schema::table('project_type', function (Blueprint $table) {
            $table->json('quota_overrides')->nullable();
        });

        Schema::table('project', function (Blueprint $table) {
            $table->json('quota_overrides')->nullable();
        });

        // Drop the quota tables
        Schema::dropIfExists('project_quota');
        Schema::dropIfExists('project_type_quota');
    }
};
