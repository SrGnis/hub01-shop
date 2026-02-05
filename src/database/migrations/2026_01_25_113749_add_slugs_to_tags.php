<?php

use App\Models\Project;
use App\Models\ProjectTag;
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
        Schema::table('project_tag_group', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name');
        });

        Schema::table('project_tag', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name');
        });

        Schema::table('project_version_tag_group', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name');
        });

        Schema::table('project_version_tag', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_tag_group', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('project_tag', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('project_version_tag_group', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('project_version_tag', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
