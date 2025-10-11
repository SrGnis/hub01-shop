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
        Schema::table('project_version_dependency', function (Blueprint $table) {
            $table->string('dependency_name')->nullable()->after('dependency_type');
            $table->string('dependency_version')->nullable()->after('dependency_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_version_dependency', function (Blueprint $table) {
            $table->dropColumn(['dependency_name', 'dependency_version']);
        });
    }
};
