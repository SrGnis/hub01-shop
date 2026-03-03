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
        Schema::create('project_version_daily_download', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_version_id')->constrained('project_version')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('downloads')->default(0);
            $table->timestamps();

            $table->unique(['project_version_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_version_daily_download');
    }
};
