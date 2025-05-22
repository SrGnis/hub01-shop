<?php

namespace App\Console\Commands;

use App\Jobs\PermanentlyDeleteProjects;
use Illuminate\Console\Command;

class DeleteExpiredProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:delete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete projects that were soft-deleted 14+ days ago';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching job to permanently delete expired projects...');
        PermanentlyDeleteProjects::dispatch();
        $this->info('Job dispatched successfully!');

        return Command::SUCCESS;
    }
}
