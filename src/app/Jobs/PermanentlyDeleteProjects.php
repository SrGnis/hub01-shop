<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PermanentlyDeleteProjects implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $cutoffDate = now()->subDays(14);

        $projects = Project::onlyTrashed()
            ->where('deleted_at', '<=', $cutoffDate)
            ->get();

        $count = $projects->count();

        if ($count > 0) {
            Log::info("Found {$count} projects to permanently delete");

            foreach ($projects as $project) {
                Log::info("Permanently deleting project: {$project->id} - {$project->name}");

                $project->forceDelete();
            }

            Log::info("Permanently deleted {$count} projects");
        } else {
            Log::info('No projects found for permanent deletion');
        }
    }
}
