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
        Schema::create('project_version_tag_group_project_type', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_type_id');
            $table->unsignedBigInteger('tag_group_id');

            $table->foreign('project_type_id')
                ->references('id')
                ->on('project_type')
                ->onDelete('cascade');

            $table->foreign('tag_group_id')
                ->references('id')
                ->on('project_version_tag_group')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['project_type_id', 'tag_group_id'], 'pvtg_pt_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_version_tag_group_project_type');
    }
};
