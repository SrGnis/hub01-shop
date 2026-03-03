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
        Schema::table('project_tag_group', function (Blueprint $table) {
            $table->integer('display_priority')->default(0)->after('name');
        });

        Schema::table('project_tag', function (Blueprint $table) {
            $table->integer('display_priority')->default(0)->after('icon');
        });

        Schema::table('project_version_tag_group', function (Blueprint $table) {
            $table->integer('display_priority')->default(0)->after('name');
        });

        Schema::table('project_version_tag', function (Blueprint $table) {
            $table->integer('display_priority')->default(0)->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_version_tag', function (Blueprint $table) {
            $table->dropColumn('display_priority');
        });

        Schema::table('project_version_tag_group', function (Blueprint $table) {
            $table->dropColumn('display_priority');
        });

        Schema::table('project_tag', function (Blueprint $table) {
            $table->dropColumn('display_priority');
        });

        Schema::table('project_tag_group', function (Blueprint $table) {
            $table->dropColumn('display_priority');
        });
    }
};
