<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DemoRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:refresh
                            {--max-retries=3 : Maximum number of retry attempts for database refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the demo database and clean up orphaned files with retry mechanism';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting demo refresh process...');
        $this->newLine();

        $maxRetries = (int) $this->option('max-retries');
        $attempt = 0;
        $success = false;

        // Attempt database refresh with retry mechanism
        while ($attempt < $maxRetries && !$success) {
            $attempt++;

            if ($attempt > 1) {
                $this->warn("Retry attempt {$attempt} of {$maxRetries}...");
            } else {
                $this->info("Attempt {$attempt} of {$maxRetries}: Running database refresh...");
            }

            try {
                // Run migrate:fresh --seed
                $exitCode = Artisan::call('migrate:fresh', [
                    '--seed' => true,
                    '--force' => true,
                ], $this->getOutput());

                if ($exitCode === 0) {
                    $success = true;
                    $this->info('✓ Database refresh completed successfully!');
                    Log::info('Demo refresh: Database refresh successful', [
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                    ]);
                } else {
                    throw new \Exception('migrate:fresh command returned non-zero exit code: ' . $exitCode);
                }
            } catch (\Exception $e) {
                $this->error("✗ Database refresh failed on attempt {$attempt}: " . $e->getMessage());

                Log::error('Demo refresh: Database refresh failed', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                if ($attempt < $maxRetries) {
                    $this->warn('Retrying in 2 seconds...');
                    sleep(2);
                } else {
                    $this->error('✗ Database refresh failed after ' . $maxRetries . ' attempts.');
                    Log::error('Demo refresh: All retry attempts exhausted', [
                        'max_retries' => $maxRetries,
                    ]);
                    return Command::FAILURE;
                }
            }
        }

        $this->newLine();

        // Run cleanup:orphaned-files after successful database refresh
        if ($success) {
            $this->info('Running orphaned files cleanup...');

            try {
                $exitCode = Artisan::call('cleanup:orphaned-files', [
                    '--force' => true,
                ], $this->getOutput());

                if ($exitCode === 0) {
                    $this->info('✓ Orphaned files cleanup completed successfully!');
                    Log::info('Demo refresh: Orphaned files cleanup successful');
                } else {
                    $this->warn('⚠ Orphaned files cleanup returned non-zero exit code: ' . $exitCode);
                    Log::warning('Demo refresh: Orphaned files cleanup returned non-zero exit code', [
                        'exit_code' => $exitCode,
                    ]);
                }

                // Display the output from the cleanup command
                $output = Artisan::output();
                if (!empty(trim($output))) {
                    $this->line($output);
                }
            } catch (\Exception $e) {
                $this->error('✗ Orphaned files cleanup failed: ' . $e->getMessage());
                Log::error('Demo refresh: Orphaned files cleanup failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Don't fail the entire command if cleanup fails
                $this->warn('⚠ Demo refresh completed but cleanup failed.');
                return Command::SUCCESS;
            }
        }

        $this->newLine();
        $this->info('✓ Demo refresh process completed successfully!');
        Log::info('Demo refresh: Process completed successfully');

        return Command::SUCCESS;
    }
}

