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
        Schema::create('collection', function (Blueprint $table) {
            $table->ulid('uid')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('visibility', ['public', 'private', 'hidden'])->default('private');
            $table->enum('system_type', ['favorites'])->nullable();
            $table->string('hidden_share_token')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'visibility']);
            $table->index(['visibility', 'updated_at']);
            $table->unique('hidden_share_token');
            $table->unique(['user_id', 'system_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection');
    }
};
