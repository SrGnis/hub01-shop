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
        // Add quota_overrides to project_type table
        Schema::table('project_type', function (Blueprint $table) {
            $table->json('quota_overrides')->nullable()->after('display_name');
        });

        // Add quota_overrides to project table
        Schema::table('project', function (Blueprint $table) {
            $table->json('quota_overrides')->nullable()->after('project_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_type', function (Blueprint $table) {
            $table->dropColumn('quota_overrides');
        });

        Schema::table('project', function (Blueprint $table) {
            $table->dropColumn('quota_overrides');
        });
    }
};
