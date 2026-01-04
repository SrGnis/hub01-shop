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
        Schema::create('abuse_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reason');
            $table->morphs('reportable'); // This creates reportable_id and reportable_type columns
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'resolved'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reportable_id', 'reportable_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abuse_reports');
    }
};
