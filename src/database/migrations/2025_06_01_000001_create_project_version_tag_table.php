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
        Schema::create('project_version_tag', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->default('lucide-tag');
            $table->unsignedBigInteger('project_version_tag_group_id')->nullable();

            $table->foreign('project_version_tag_group_id')
                ->references('id')
                ->on('project_version_tag_group')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_version_tag');
    }
};
