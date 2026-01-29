<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectVersionCollection;
use App\Http\Resources\ProjectVersionResource;
use App\Models\Project;
use App\Models\ProjectVersionTag;
use App\Services\ProjectVersionService;
use Illuminate\Http\Request;

class ProjectVersionController extends Controller
{
    protected ProjectVersionService $projectVersionService;

    public function __construct(ProjectVersionService $projectVersionService)
    {
        $this->projectVersionService = $projectVersionService;
    }

    /**
     * Get all versions of a project with filtering and sorting capabilities.
     *
     * @queryParam tags array Version tag slugs to filter by.
     * @queryParam order_by string Field to order by (version, release_date, downloads). Default: release_date
     * @queryParam order_direction string Order direction (asc, desc). Default: desc
     * @queryParam per_page int Number of results per page (10, 25, 50, 100). Default: 10
     * @queryParam release_date_period string Release date period (all, last_30_days, last_90_days, last_year, custom). Default: all
     * @queryParam release_date_start string Custom start date (YYYY-MM-DD). Required when release_date_period=custom
     * @queryParam release_date_end string Custom end date (YYYY-MM-DD). Required when release_date_period=custom
     */
    public function getProjectVersions(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        // Extract query parameters with defaults
        $versionTagSlugs = $request->query('tags', []);
        $orderBy = $request->query('order_by', 'release_date');
        $orderDirection = $request->query('order_direction', 'desc');
        $perPage = $request->query('per_page', 10);
        $releaseDatePeriod = $request->query('release_date_period', 'all');
        $releaseDateStart = $request->query('release_date_start');
        $releaseDateEnd = $request->query('release_date_end');

        // Validate parameters
        $validOrderBy = ['version', 'release_date', 'downloads'];
        if (!in_array($orderBy, $validOrderBy)) {
            $orderBy = 'release_date';
        }

        $validOrderDirection = ['asc', 'desc'];
        if (!in_array($orderDirection, $validOrderDirection)) {
            $orderDirection = 'desc';
        }

        $validPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $validPerPage)) {
            $perPage = 10;
        }

        $validReleaseDatePeriod = ['all', 'last_30_days', 'last_90_days', 'last_year', 'custom'];
        if (!in_array($releaseDatePeriod, $validReleaseDatePeriod)) {
            $releaseDatePeriod = 'all';
        }

        // Ensure tags are arrays
        if (!is_array($versionTagSlugs)) {
            $versionTagSlugs = [];
        }

        // Convert version tag slugs to IDs
        $selectedVersionTags = [];
        if (!empty($versionTagSlugs)) {
            $versionTags = ProjectVersionTag::whereIn('slug', $versionTagSlugs)
                ->pluck('id')
                ->toArray();
            $selectedVersionTags = $versionTags;
        }

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
        return ProjectVersionCollection::make($paginator);
    }

    /**
     * Get a specific project version by version string.
     */
    public function getProjectVersionBySlug(Request $request, string $slug, string $version)
    {
        $project = Project::where('slug', $slug)->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $projectVersion = $this->projectVersionService->getProjectVersionByVersionString($project, $version);

        if (!$projectVersion) {
            return response()->json(['message' => 'Version not found'], 404);
        }

        return ProjectVersionResource::make($projectVersion);
    }
}
