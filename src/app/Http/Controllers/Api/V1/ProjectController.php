<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectCollection;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectTypeResource;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectType;
use App\Models\ProjectVersionTag;
use App\Services\ProjectService;
use App\Services\ProjectVersionService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    protected ProjectService $projectService;
    protected ProjectVersionService $projectVersionService;

    public function __construct(ProjectService $projectService, ProjectVersionService $projectVersionService)
    {
        $this->projectService = $projectService;
        $this->projectVersionService = $projectVersionService;
    }

    /**
     * Get all project types.
     */
    public function getProjectTypes(Request $request){
        $projectTypes = ProjectType::all();
        return ProjectTypeResource::collection($projectTypes);
    }

    /**
     * Get a project type by its slug.
     */
    public function getProjectTypeBySlug(Request $request, string $slug){
        $projectType = ProjectType::where('value', $slug)->first();

        if(!$projectType){
            return response()->json(['message' => 'Project type not found'], 404);
        }

        return ProjectTypeResource::make($projectType);
    }

    /**
     * Get a project by its slug.
     */
    public function getProjectBySlug(Request $request, string $slug){
        $project = Project::where('slug', $slug)->first();

        if(!$project){
            return response()->json(['message' => 'Project not found'], 404);
        }

        return ProjectResource::make($project);
    }

    /**
     * Get all projects with search and filtering capabilities.
     *
     * @queryParam search string Search query for project names. Example: cataclysm
     * @queryParam project_type string Project type value (optional, defaults to first type). Example: mod
     * @queryParam tags array Project tag slugs to filter by. Example: ["graphics", "gameplay"]
     * @queryParam version_tags array Version tag slugs to filter by. Example: ["stable", "experimental"]
     * @queryParam order_by string Field to order by (name, created_at, latest_version, downloads). Default: downloads
     * @queryParam order_direction string Order direction (asc, desc). Default: desc
     * @queryParam per_page int Number of results per page (10, 25, 50, 100). Default: 10
     * @queryParam release_date_period string Release date period (all, last_30_days, last_90_days, last_year, custom). Default: all
     * @queryParam release_date_start string Custom start date (YYYY-MM-DD). Required when release_date_period=custom
     * @queryParam release_date_end string Custom end date (YYYY-MM-DD). Required when release_date_period=custom
     */
    public function getProjects(Request $request)
    {
        // Get or default project type
        $projectTypeValue = $request->query('project_type');
        $projectType = $projectTypeValue
            ? ProjectType::where('value', $projectTypeValue)->firstOrFail()
            : ProjectType::first();

        // Extract query parameters with defaults
        $search = $request->query('search', '');
        $tagSlugs = $request->query('tags', []);
        $versionTagSlugs = $request->query('version_tags', []);
        $orderBy = $request->query('order_by', 'downloads');
        $orderDirection = $request->query('order_direction', 'desc');
        $resultsPerPage = $request->query('per_page', 10);
        $releaseDatePeriod = $request->query('release_date_period', 'all');
        $releaseDateStart = $request->query('release_date_start');
        $releaseDateEnd = $request->query('release_date_end');

        // Validate parameters
        $validOrderBy = ['name', 'created_at', 'latest_version', 'downloads'];
        if (!in_array($orderBy, $validOrderBy)) {
            $orderBy = 'downloads';
        }

        $validOrderDirection = ['asc', 'desc'];
        if (!in_array($orderDirection, $validOrderDirection)) {
            $orderDirection = 'desc';
        }

        $validPerPage = [10, 25, 50, 100];
        if (!in_array($resultsPerPage, $validPerPage)) {
            $resultsPerPage = 10;
        }

        $validReleaseDatePeriod = ['all', 'last_30_days', 'last_90_days', 'last_year', 'custom'];
        if (!in_array($releaseDatePeriod, $validReleaseDatePeriod)) {
            $releaseDatePeriod = 'all';
        }

        // Ensure tags are arrays
        if (!is_array($tagSlugs)) {
            $tagSlugs = [];
        }

        if (!is_array($versionTagSlugs)) {
            $versionTagSlugs = [];
        }

        // Convert tag slugs to IDs, validating they exist in the database
        $selectedTags = [];
        if (!empty($tagSlugs)) {
            $tags = ProjectTag::whereIn('slug', $tagSlugs)
                ->pluck('id')
                ->toArray();
            $selectedTags = $tags;
        }

        // Convert version tag slugs to IDs, validating they exist in the database
        $selectedVersionTags = [];
        if (!empty($versionTagSlugs)) {
            $versionTags = ProjectVersionTag::whereIn('slug', $versionTagSlugs)
                ->pluck('id')
                ->toArray();
            $selectedVersionTags = $versionTags;
        }

        // Get paginated results from service
        $paginator = $this->projectService->searchProjects(
            projectType: $projectType,
            search: $search,
            selectedTags: $selectedTags,
            selectedVersionTags: $selectedVersionTags,
            orderBy: $orderBy,
            orderDirection: $orderDirection,
            resultsPerPage: $resultsPerPage,
            releaseDatePeriod: $releaseDatePeriod,
            releaseDateStart: $releaseDateStart,
            releaseDateEnd: $releaseDateEnd,
            exclude: ['description']
        );

        // Return paginated JSON response
        return ProjectCollection::make($paginator);
    }
}
