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
        Schema::create('pending_email_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('old_email');
            $table->string('new_email');
            $table->string('authorization_token')->unique();
            $table->string('verification_token')->nullable()->unique();
            $table->enum('status', ['pending_authorization', 'pending_verification', 'completed'])->default('pending_authorization');
            $table->timestamp('authorization_expires_at');
            $table->timestamp('verification_expires_at')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_email_changes');
    }
};

