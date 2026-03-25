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
        Schema::create('collection_entry', function (Blueprint $table) {
            $table->ulid('uid')->primary();
            $table->foreignUlid('collection_uid')->constrained('collection', 'uid')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('project')->nullOnDelete();
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['collection_uid', 'project_id']);
            $table->index(['collection_uid', 'sort_order']);
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_entry');
    }
};
