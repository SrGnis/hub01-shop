<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {--name= : The name of the admin user} {--email= : The email of the admin user} {--password= : The password for the admin user} {--promote= : Promote an existing user to admin by email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user or promote an existing user to admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if we're promoting an existing user
        if ($promoteEmail = $this->option('promote')) {
            return $this->promoteExistingUser($promoteEmail);
        }

        // Get or prompt for user details
        $name = $this->option('name') ?: $this->ask('Enter admin name');
        $email = $this->option('email') ?: $this->ask('Enter admin email');
        $password = $this->option('password') ?: $this->secret('Enter admin password');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        // Create the admin user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $this->info("Admin user {$user->name} created successfully!");

        return 0;
    }

    /**
     * Promote an existing user to admin
     */
    protected function promoteExistingUser(string $email): int
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");

            return 1;
        }

        if ($user->isAdmin()) {
            $this->info("User {$user->name} is already an admin.");

            return 0;
        }

        $user->role = 'admin';
        $user->save();

        $this->info("User {$user->name} has been promoted to admin.");

        return 0;
    }
}
