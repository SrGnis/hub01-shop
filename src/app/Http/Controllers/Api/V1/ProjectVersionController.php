<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectVersionCollection;
use App\Http\Resources\ProjectVersionResource;
use App\Models\Project;
use App\Models\ProjectVersionTag;
use App\Services\ProjectVersionService;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;

class ProjectVersionController extends Controller
{
    protected ProjectVersionService $projectVersionService;

    public function __construct(ProjectVersionService $projectVersionService)
    {
        $this->projectVersionService = $projectVersionService;
    }

    /**
     * List all versions of a project.
     *
     */
    #[PathParameter(name: 'slug', description: 'The project slug')]
    #[QueryParameter(name: 'tags[]', description: 'The version tags slugs to filter by', example: 'tags[]=tag1&tags[]=tag2')]
    #[QueryParameter(name: 'order_by', description: 'The field to order by', default: 'downloads')]
    #[QueryParameter(name: 'order_direction', description: 'The direction to order by', default: 'desc')]
    #[QueryParameter(name: 'per_page', description: 'The number of results per page', default: 10)]
    #[QueryParameter(name: 'release_date_period', description: 'The release date period to filter by', default: 'all')]
    #[QueryParameter(name: 'release_date_start', description: 'The start date to filter by, only used if release_date_period is custom')]
    #[QueryParameter(name: 'release_date_end', description: 'The end date to filter by, only used if release_date_period is custom')]
    public function getProjectVersions(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->first();

        abort_if(!$project, 404, 'Project not found');

        // Validate all query parameters
        $validated = $request->validate([
            'tags' => 'nullable|array',
            'tags.*' => 'string|exists:project_version_tags,slug',
            'order_by' => 'nullable|string|in:version,release_date,downloads',
            'order_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'release_date_period' => 'nullable|string|in:all,last_30_days,last_90_days,last_year,custom',
            'release_date_start' => 'nullable|date',
            'release_date_end' => 'nullable|date|after_or_equal:release_date_start',
        ]);

        // Extract validated parameters with defaults
        $versionTagSlugs = $validated['tags'] ?? [];
        $orderBy = $validated['order_by'] ?? 'release_date';
        $orderDirection = $validated['order_direction'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 10;
        $releaseDatePeriod = $validated['release_date_period'] ?? 'all';
        $releaseDateStart = $validated['release_date_start'] ?? null;
        $releaseDateEnd = $validated['release_date_end'] ?? null;

        // Convert version tag slugs to IDs
        $selectedVersionTags = !empty($versionTagSlugs)
            ? ProjectVersionTag::whereIn('slug', $versionTagSlugs)->pluck('id')->toArray()
            : [];

        $with = [
            'tags',
            'dependencies',
            'files'
        ];

        // Get paginated results from service
        $paginator = $this->projectVersionService->getProjectVersions(
            $project,
            $selectedVersionTags,
            $orderBy,
            $orderDirection,
            $perPage,
            $releaseDatePeriod,
            $releaseDateStart,
            $releaseDateEnd,
            $with
        );

        // Return paginated JSON response
        return ProjectVersionResource::collection($paginator);
    }

    /**
     * Get a project version
     */
    #[PathParameter(name: 'slug', description: 'The project slug')]
    #[PathParameter(name: 'version', description: 'The project version')]
    public function getProjectVersionBySlug(Request $request, string $slug, string $version)
    {
        $project = Project::where('slug', $slug)->first();

        abort_if(!$project, 404, 'Project not found');

        $projectVersion = $this->projectVersionService->getProjectVersionByVersionString($project, $version);

        abort_if(!$projectVersion, 404, 'Project version not found');

        return ProjectVersionResource::make($projectVersion);
    }
}
