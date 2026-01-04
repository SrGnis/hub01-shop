<?php

namespace App\Console\Commands;

use App\Services\CleanupService;
use Illuminate\Console\Command;

class CleanupOrphanedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:orphaned-files
                            {--list-only : Only list orphaned files without deleting them}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned storage files that are not referenced in the database';

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
        $this->info('Scanning for orphaned files...');

        $orphanedFiles = $this->cleanupService->listOrphanedFiles();

        if ($orphanedFiles->isEmpty()) {
            $this->info('No orphaned files found.');

            return Command::SUCCESS;
        }

        $this->info("Found {$orphanedFiles->count()} orphaned file(s):");
        $this->newLine();

        // Display orphaned files in a table
        $tableData = $orphanedFiles->map(function ($file) {
            return [
                'Disk' => $file['disk'],
                'Path' => $file['path'],
                'Size' => $this->formatBytes($file['size']),
                'Last Modified' => date('Y-m-d H:i:s', $file['last_modified']),
            ];
        })->toArray();

        $this->table(
            ['Disk', 'Path', 'Size', 'Last Modified'],
            $tableData
        );

        $totalSize = $orphanedFiles->sum('size');
        $this->info("Total size: {$this->formatBytes($totalSize)}");
        $this->newLine();

        // If list-only mode, exit here
        if ($this->option('list-only')) {
            return Command::SUCCESS;
        }

        // Confirm deletion unless --force is used
        if (! $this->option('force')) {
            if (! $this->confirm('Do you want to delete these orphaned files?', false)) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        $this->info('Deleting orphaned files...');
        $deletedCount = $this->cleanupService->deleteOrphanedFiles();

        $this->info("Successfully deleted {$deletedCount} orphaned file(s).");

        return Command::SUCCESS;
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
