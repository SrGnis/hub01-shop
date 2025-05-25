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
        Schema::create('project_version_dependency', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_version_id');
            $table->unsignedBigInteger('dependency_project_version_id')->nullable();
            $table->unsignedBigInteger('dependency_project_id')->nullable();
            $table->enum('dependency_type', ['required', 'optional', 'embedded'])->default('required');
            $table->timestamps();

            $table->foreign('project_version_id')->references('id')->on('project_version')->cascadeOnDelete();
            $table->foreign('dependency_project_version_id')->references('id')->on('project_version')->nullOnDelete();
            $table->foreign('dependency_project_id')->references('id')->on('project')->nullOnDelete();

            // A dependency must reference either a specific project version or a general project
            // We can't use check constraints in Laravel migrations, so we'll enforce this in the application logic
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_version_dependency');
    }
};
