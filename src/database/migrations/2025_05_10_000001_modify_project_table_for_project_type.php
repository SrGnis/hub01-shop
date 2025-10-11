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
        // First, add the new project_type_id column
        Schema::table('project', function (Blueprint $table) {
            $table->unsignedBigInteger('project_type_id')->after('status');
            $table->foreign('project_type_id')->references('id')->on('project_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the project_type_id column
        Schema::table('project', function (Blueprint $table) {
            $table->dropForeign(['project_type_id']);
            $table->dropColumn('project_type_id');
        });
    }
};
