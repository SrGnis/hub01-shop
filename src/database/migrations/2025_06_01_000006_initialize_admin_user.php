<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (User::count() === 0) {
            User::create([
                'name' => 'admin',
                'email' => env('APP_ADMIN_EMAIL', 'admin@example.com'),
                'email_verified_at' => now(),
                'password' => Hash::make(env('APP_ADMIN_PASSWORD', 'admin')),
                'role' => 'admin',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
