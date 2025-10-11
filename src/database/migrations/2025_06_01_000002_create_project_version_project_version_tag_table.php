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
        Schema::create('project_version_project_version_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_version_id');
            $table->unsignedBigInteger('tag_id');

            $table->foreign('project_version_id')
                ->references('id')
                ->on('project_version')
                ->onDelete('cascade');

            $table->foreign('tag_id')
                ->references('id')
                ->on('project_version_tag')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['project_version_id', 'tag_id'], 'pv_pvt_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_version_project_version_tag');
    }
};
