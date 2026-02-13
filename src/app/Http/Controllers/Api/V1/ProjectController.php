<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectCollection;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectTypeCollection;
use App\Http\Resources\ProjectTypeResource;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectType;
use App\Models\ProjectVersionTag;
use App\Services\ProjectService;
use App\Services\ProjectVersionService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
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
     * List project types.
     *
     * Returns all the project types defined in the application
     */
    #[Group('Project Types')]
    public function getProjectTypes(Request $request){
        $projectTypes = ProjectType::all();

        return ProjectTypeResource::collection($projectTypes);
    }

    /**
     * Get a project type.
     *
     * Returns the project type with the given slug
     */
    #[Group('Project Types')]
    public function getProjectTypeBySlug(Request $request, string $slug){
        $projectType = ProjectType::where('value', $slug)->first();

        if(!$projectType){
            abort(404, 'Project type not found');
        }

        return ProjectTypeResource::make($projectType);
    }

    /**
     * Get a project
     *
     * Returns the project with the given slug
     */
    #[Group('Projects')]
    public function getProjectBySlug(Request $request, string $slug){
        $project = Project::accessScope()->where('slug', $slug)->first();

        if(!$project){
            return response()->json(['message' => 'Project not found'], 404);
        }

        return ProjectResource::make($project);
    }

    /**
     * Search projects
     *
     * Returns a filtered list of projects
     */
    #[Group('Projects')]
    #[QueryParameter(name: 'project_type', description: 'The project type to filter by', default: 'mod')]
    #[QueryParameter(name: 'search', description: 'The search query to filter by')]
    #[QueryParameter(name: 'tags[]', description: 'The tags slugs to filter by', example: 'tags[]=tag1&tags[]=tag2')]
    #[QueryParameter(name: 'version_tags[]', description: 'The version tags slugs to filter by', example: 'version_tags[]=tag1&version_tags[]=tag2')]
    #[QueryParameter(name: 'order_by', description: 'The field to order by', default: 'downloads')]
    #[QueryParameter(name: 'order_direction', description: 'The direction to order by', default: 'desc')]
    #[QueryParameter(name: 'per_page', description: 'The number of results per page', default: 10)]
    #[QueryParameter(name: 'release_date_period', description: 'The release date period to filter by', default: 'all')]
    #[QueryParameter(name: 'release_date_start', description: 'The start date to filter by, only used if release_date_period is custom')]
    #[QueryParameter(name: 'release_date_end', description: 'The end date to filter by, only used if release_date_period is custom')]
    public function getProjects(Request $request)
    {

        // Validate all query parameters
        $validated = $request->validate([
            'project_type' => 'nullable|string|exists:project_type,value',
            'search' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|exists:project_tag,slug',
            'version_tags' => 'nullable|array',
            'version_tags.*' => 'string|exists:project_version_tag,slug',
            'order_by' => 'nullable|string|in:name,created_at,updated_at,downloads',
            'order_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'release_date_period' => 'nullable|string|in:all,last_30_days,last_90_days,last_year,custom',
            'release_date_start' => 'nullable|date',
            'release_date_end' => 'nullable|date|after_or_equal:release_date_start',
        ]);

        // Get or default project type
        $projectType = $validated['project_type'] ?? null
            ? ProjectType::where('value', $validated['project_type'])->firstOrFail()
            : ProjectType::first();

        // Extract validated parameters with defaults
        $search = $validated['search'] ?? '';
        $tagSlugs = $validated['tags'] ?? [];
        $versionTagSlugs = $validated['version_tags'] ?? [];
        $orderBy = $validated['order_by'] ?? 'downloads';
        $orderDirection = $validated['order_direction'] ?? 'desc';
        $resultsPerPage = $validated['per_page'] ?? 10;
        $releaseDatePeriod = $validated['release_date_period'] ?? 'all';
        $releaseDateStart = $validated['release_date_start'] ?? null;
        $releaseDateEnd = $validated['release_date_end'] ?? null;

        // Convert tag slugs to IDs
        $selectedTags = !empty($tagSlugs)
            ? ProjectTag::whereIn('slug', $tagSlugs)->pluck('id')->toArray()
            : [];

        // Convert version tag slugs to IDs
        $selectedVersionTags = !empty($versionTagSlugs)
            ? ProjectVersionTag::whereIn('slug', $versionTagSlugs)->pluck('id')->toArray()
            : [];

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
        return ProjectResource::collection($paginator);
    }
}
