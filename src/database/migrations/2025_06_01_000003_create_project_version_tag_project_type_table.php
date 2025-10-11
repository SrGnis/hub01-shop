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
        Schema::create('project_version_tag_project_type', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_type_id');
            $table->unsignedBigInteger('tag_id');

            $table->foreign('project_type_id')
                ->references('id')
                ->on('project_type')
                ->onDelete('cascade');

            $table->foreign('tag_id')
                ->references('id')
                ->on('project_version_tag')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['project_type_id', 'tag_id'], 'pvt_pt_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_version_tag_project_type');
    }
};
