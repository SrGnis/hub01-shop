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
        Schema::create('project_version', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version');
            $table->text('changelog')->nullable();
            $table->enum('release_type', ['alpha', 'beta', 'rc', 'release'])->default('release');
            $table->date('release_date');
            $table->unsignedInteger('downloads')->default(0);
            $table->unsignedBigInteger('project_id');

            $table->foreign('project_id')->references('id')->on('project')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mod_version');
    }
};
