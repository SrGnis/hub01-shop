<?php

namespace App\Console\Commands;

use App\Services\CleanupService;
use Illuminate\Console\Command;

class CleanupUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:unverified-users
                            {--send-warnings : Send deletion warning emails to eligible users}
                            {--delete : Delete unverified users past the deletion threshold}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up unverified users by sending warnings and deleting expired accounts';

    protected CleanupService $cleanupService;

    /**
     * Create a new command instance.
     */
    public function __construct(CleanupService $cleanupService)
    {
        parent::__construct();
        $this->cleanupService = $cleanupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sendWarnings = $this->option('send-warnings');
        $deleteUsers = $this->option('delete');

        // If no options provided, show help
        if (! $sendWarnings && ! $deleteUsers) {
            $this->error('Please specify at least one action: --send-warnings or --delete');
            $this->info('Use --help for more information');

            return Command::FAILURE;
        }

        $success = true;

        // Send deletion warnings
        if ($sendWarnings) {
            $success = $this->handleSendWarnings() && $success;
        }

        // Delete unverified users
        if ($deleteUsers) {
            $success = $this->handleDeleteUsers() && $success;
        }

        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Handle sending deletion warnings
     */
    private function handleSendWarnings(): bool
    {
        $this->info('Checking for users eligible for deletion warnings...');

        $warningThreshold = config('cleanup.unverified_users.warning_threshold_days');
        $deletionThreshold = config('cleanup.unverified_users.deletion_threshold_days');

        $eligibleUsers = $this->cleanupService->listUnverifiedUsers($warningThreshold)
            ->whereNull('unverified_deletion_warning_sent_at');

        if ($eligibleUsers->isEmpty()) {
            $this->info('No users eligible for deletion warnings.');

            return true;
        }

        $this->info("Found {$eligibleUsers->count()} user(s) eligible for deletion warnings:");
        $this->newLine();

        // Display eligible users in a table
        $tableData = $eligibleUsers->map(function ($user) use ($warningThreshold, $deletionThreshold) {
            $daysOld = now()->diffInDays($user->created_at);
            $daysUntilDeletion = $deletionThreshold - $daysOld;

            return [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Registered' => $user->created_at->format('Y-m-d'),
                'Days Old' => $daysOld,
                'Days Until Deletion' => max(0, $daysUntilDeletion),
            ];
        })->toArray();

        $this->table(
            ['ID', 'Name', 'Email', 'Registered', 'Days Old', 'Days Until Deletion'],
            $tableData
        );

        // Confirm sending warnings unless --force is used
        if (! $this->option('force')) {
            if (! $this->confirm('Do you want to send deletion warnings to these users?', false)) {
                $this->info('Operation cancelled.');

                return true;
            }
        }

        $this->info('Sending deletion warnings...');
        $warningsSent = $this->cleanupService->sendDeletionWarnings();

        $this->info("Successfully sent {$warningsSent} deletion warning(s).");

        return true;
    }

    /**
     * Handle deleting unverified users
     */
    private function handleDeleteUsers(): bool
    {
        $this->info('Checking for users eligible for deletion...');

        $deletionThreshold = config('cleanup.unverified_users.deletion_threshold_days');
        $eligibleUsers = $this->cleanupService->listUnverifiedUsers($deletionThreshold);

        if ($eligibleUsers->isEmpty()) {
            $this->info('No users eligible for deletion.');

            return true;
        }

        $this->info("Found {$eligibleUsers->count()} user(s) eligible for deletion:");
        $this->newLine();

        // Display eligible users in a table
        $tableData = $eligibleUsers->map(function ($user) {
            $daysOld = now()->diffInDays($user->created_at);
            $warningSent = $user->unverified_deletion_warning_sent_at
                ? $user->unverified_deletion_warning_sent_at->format('Y-m-d')
                : 'No';

            return [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Registered' => $user->created_at->format('Y-m-d'),
                'Days Old' => $daysOld,
                'Warning Sent' => $warningSent,
            ];
        })->toArray();

        $this->table(
            ['ID', 'Name', 'Email', 'Registered', 'Days Old', 'Warning Sent'],
            $tableData
        );

        // Confirm deletion unless --force is used
        if (! $this->option('force')) {
            $this->warn('This will permanently delete these user accounts!');
            if (! $this->confirm('Are you sure you want to delete these users?', false)) {
                $this->info('Operation cancelled.');

                return true;
            }
        }

        $this->info('Deleting unverified users...');

        try {
            $deletedCount = $this->cleanupService->deleteUnverifiedUsers();
            $this->info("Successfully deleted {$deletedCount} user(s).");

            return true;
        } catch (\Exception $e) {
            $this->error('Failed to delete users: '.$e->getMessage());

            return false;
        }
    }
}
