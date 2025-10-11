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
        Schema::table('membership', function (Blueprint $table) {
            $table->enum('status', ['active', 'pending', 'rejected'])->default('active')->after('primary');
            $table->unsignedBigInteger('invited_by')->nullable()->after('status');
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership', function (Blueprint $table) {
            $table->dropForeign(['invited_by']);
            $table->dropColumn(['status', 'invited_by']);
        });
    }
};
