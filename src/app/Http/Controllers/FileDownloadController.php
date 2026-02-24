<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
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

        $fingerprint = hash('sha256', implode('|', [
            $request->ip(),
            $request->userAgent() ?? 'unknown-agent',
            $projectType->id,
            $project->id,
            $version->id,
            $fileModel->id,
        ]));

        $dedupeKey = 'download_dedupe:'.$fingerprint;
        $shouldCount = Cache::add($dedupeKey, true, now()->addHours(3));

        if ($shouldCount) {
            $version->increment('downloads');

            $dailyDownload = $version->dailyDownloads()->firstOrCreate(
                ['date' => today()->toDateString()],
                ['downloads' => 0]
            );

            $dailyDownload->increment('downloads');
        }

        return Storage::disk(ProjectFile::getDisk())->download(
            $fileModel->path,
            $fileModel->name,
            ['Content-Type' => 'application/octet-stream']
        );
    }
}
