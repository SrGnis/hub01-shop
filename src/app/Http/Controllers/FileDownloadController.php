<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectType;
use App\Services\DownloadStatsUserAgentFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
    public function __construct(private readonly DownloadStatsUserAgentFilterService $downloadStatsUserAgentFilterService)
    {
    }

    /**
     * Download a project file
     *
     * @param  string  $version  Version string (route key)
     * @param  string  $file  File name (route key)
     * @return StreamedResponse
     */
    public function download(Request $request, ProjectType $projectType, Project $project, $version, $file)
    {

        // Check if the project is deactivated
        if ($project->isDeactivated()) {
            abort(404, 'Project not found');
        }

        $version = $project->versions()->where('version', $version)->first();

        if (! $version) {
            abort(404, 'Version not found');
        }

        $fileModel = $version->files()->where('name', $file)->first();

        if (! $fileModel) {
            abort(404, 'File not found');
        }

        if (! Storage::exists($fileModel->path)) {
            abort(404, 'File not found');
        }

        $shouldCount = false;
        $userAgent = $request->userAgent();

        if (! $this->downloadStatsUserAgentFilterService->shouldIgnore($userAgent)) {
            $fingerprint = hash('sha256', implode('|', [
                $request->ip(),
                $userAgent ?? 'unknown-agent',
                $projectType->id,
                $project->id,
                $version->id,
                $fileModel->id,
            ]));

            $dedupeKey = 'download_dedupe:'.$fingerprint;
            $shouldCount = Cache::add($dedupeKey, true, now()->addHours(3));
        }

        if ($shouldCount) {
            $now = now();
            $today = $now->toDateString();
            DB::table('project_version_daily_download')->upsert(
                [[
                    'project_version_id' => $version->id,
                    'date' => $today,
                    'downloads' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['project_version_id', 'date'],
                [
                    'downloads' => DB::raw('downloads + 1'),
                    'updated_at' => $now,
                ]
            );
        }

        return Storage::disk(ProjectFile::getDisk())->download(
            $fileModel->path,
            $fileModel->name,
            ['Content-Type' => 'application/octet-stream']
        );
    }
}
