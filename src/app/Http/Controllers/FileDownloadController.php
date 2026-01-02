<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectType;
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
    public function download(ProjectType $projectType, Project $project, $version, $file)
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

        $version->increment('downloads');

        if (! Storage::exists($fileModel->path)) {
            abort(404, 'File not found');
        }

        return Storage::download(
            $fileModel->path,
            $fileModel->name,
            ['Content-Type' => 'application/octet-stream']
        );
    }
}
