<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SendTestVerificationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-test-verification-email {email? : The email address to send the test to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test verification email to check styling';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? $this->ask('Enter an email address to send the test to:');

        // Check if user exists
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create a temporary user for testing
            $user = new User([
                'name' => 'Test User',
                'email' => $email,
                'password' => Hash::make('password'),
            ]);

            $this->info("Creating a temporary user for testing (will not be saved to database).");
        } else {
            $this->info("Using existing user: {$user->name} <{$user->email}>.");
        }

        // Send the verification email
        $user->notify(new CustomVerifyEmail());

        $this->info("Verification email sent to {$email}.");
        $this->info("Check your log file at: storage/logs/laravel.log");

        return Command::SUCCESS;
    }
}
