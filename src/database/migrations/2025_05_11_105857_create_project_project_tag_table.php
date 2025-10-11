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
        Schema::create('project_project_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('tag_id');

            $table->foreign('project_id')->references('id')->on('project')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('project_tag')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['project_id', 'tag_id'], 'p_pt_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_project_tag');
    }
};
