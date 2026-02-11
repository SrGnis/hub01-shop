<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectVersionCollection;
use App\Http\Resources\ProjectVersionResource;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionTag;
use App\Services\ProjectQuotaService;
use App\Services\ProjectVersionService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProjectVersionController extends Controller
{
    protected ProjectVersionService $projectVersionService;
    protected ProjectQuotaService $quotaService;

    public function __construct(ProjectVersionService $projectVersionService, ProjectQuotaService $quotaService)
    {
        $this->projectVersionService = $projectVersionService;
        $this->quotaService = $quotaService;
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
            'tags.*' => 'string|exists:project_version_tag,slug',
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

    /**
     * Create a new project version
     *
     * @requestMediaType multipart/form-data
     */
    #[HeaderParameter(name: 'Authorization', description: 'Bearer token', type: 'string', required: true, example: 'Bearer {token}')]
    #[PathParameter(name: 'slug', description: 'The project slug')]
    #[BodyParameter('name', description: 'The name of the version', type: 'string', required: true, example: 'Version 1.0.0')]
    #[BodyParameter('version', description: 'The version number (must be unique for the project)', type: 'string', required: true, example: '1.0.0')]
    #[BodyParameter('release_type', description: 'The release type: `release`, `rc`, `beta`, or `alpha`', type: 'string', required: true, example: 'release')]
    #[BodyParameter('release_date', description: 'The release date', type: 'string', format: 'date', required: true, example: '2024-01-15')]
    #[BodyParameter('changelog', description: 'The changelog for this version', type: 'string', required: false, example: 'Initial release')]
    #[BodyParameter('files[]', description: 'Array of files to upload', type: 'array', required: true)]
    #[BodyParameter('files[].*', description: 'File to upload', type: 'file', required: true)]
    #[BodyParameter('tags[]', description: 'Array of version tag slugs', type: 'array', required: false)]
    #[BodyParameter('dependencies[]', description: 'Array of dependencies', type: 'array', required: false)]
    #[BodyParameter('dependencies[].*.project', description: 'Project slug of the dependency', type: 'string', required: true)]
    #[BodyParameter('dependencies[].*.version', description: 'Version slug of the dependency', type: 'string', required: false)]
    #[BodyParameter('dependencies[].*.type', description: 'Dependency type: `required`, `optional` or `embedded`', type: 'string', required: true, example: 'required')]
    #[BodyParameter('dependencies[].*.external', description: 'If the dependency is linked to a project in the platform or is external', type: 'boolean', required: true, default: true)]
    public function store(Request $request, string $slug)
    {
        logger()->debug($request);

        $project = Project::where('slug', $slug)->first();

        abort_if(!$project, 404, 'Project not found');

        // Check if the project is deactivated
        abort_if($project->isDeactivated(), 403, 'This project has been deactivated and versions cannot be created.');

        // Authorization check
        abort_unless(Gate::allows('uploadVersion', $project), 403, 'You do not have permission to upload versions to this project.');

        // Validate request
        $validated = $request->validate($this->getStoreValidationRules($project, $request->input('dependencies', [])));

        // Prepare version data
        $versionData = [
            'name' => $validated['name'],
            'version' => $validated['version'],
            'release_type' => $validated['release_type'],
            'release_date' => $validated['release_date'],
            'changelog' => $validated['changelog'] ?? null,
        ];

        // Prepare dependencies - convert slugs to IDs
        $dependencies = $this->convertDependencySlugsToIds($validated['dependencies'] ?? []);

        // Prepare tags
        $tags = $validated['tags'] ?? [];

        // Convert version tag slugs to IDs
        $tags = !empty($tags)
            ? ProjectVersionTag::whereIn('slug', $tags)->pluck('id')->toArray()
            : [];

        // Prepare files
        $files = $validated['files'] ?? [];

        try {
            // Create version using service
            $projectVersion = $this->projectVersionService->saveVersion(
                $project,
                $versionData,
                $files,
                [], // No existing files for new version
                $dependencies,
                $tags,
                null // No existing version
            );

            return ProjectVersionResource::make($projectVersion->load(['tags','dependencies','files']))
                ->additional(['message' => 'Version created successfully'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            abort(500, 'Failed to create version: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing project version
     *
     * @requestMediaType multipart/form-data
     */
    #[HeaderParameter(name: 'Authorization', description: 'Bearer token', type: 'string', required: true, example: 'Bearer {token}')]
    #[PathParameter(name: 'slug', description: 'The project slug')]
    #[PathParameter(name: 'version', description: 'The project version')]
    #[BodyParameter('name', description: 'The name of the version', type: 'string', required: true, example: 'Version 1.0.1')]
    #[BodyParameter('version', description: 'The version number (must be unique for the project)', type: 'string', required: true, example: '1.0.1')]
    #[BodyParameter('release_type', description: 'The release type', type: 'string', required: true, example: 'release')]
    #[BodyParameter('release_date', description: 'The release date', type: 'string', format: 'date', required: true, example: '2024-01-20')]
    #[BodyParameter('changelog', description: 'The changelog for this version', type: 'string', required: false, example: 'Bug fixes')]
    #[BodyParameter('tags[]', description: 'Array of version tag slugs', type: 'array', required: false)]
    #[BodyParameter('files[]', description: 'Array of new files to upload', type: 'array', required: false)]
    #[BodyParameter('files[].*', description: 'File to upload', type: 'file', required: false)]
    #[BodyParameter('files_to_remove[]', description: 'Array of names of existing files to delete', type: 'array', required: false, example: ['file1.txt', 'file2.txt'])]
    #[BodyParameter('clean_existing_files', description: 'Boolean to indicate if existing files should be deleted', type: 'boolean', required: false)]
    #[BodyParameter('dependencies[]', description: 'Array of dependencies', type: 'array', required: false)]
    #[BodyParameter('dependencies[].*.project', description: 'Project slug of the dependency', type: 'string', required: true)]
    #[BodyParameter('dependencies[].*.version', description: 'Version slug of the dependency', type: 'string', required: false)]
    #[BodyParameter('dependencies[].*.type', description: 'Dependency type', type: 'string', required: true, default: 'required')]
    #[BodyParameter('dependencies[].*.external', description: 'If the dependency is linked to a project in the platform or is external', type: 'boolean', required: true, default: true)]
    public function update(Request $request, string $slug, string $version)
    {
        // Find the project
        $project = Project::where('slug', $slug)->first();

        abort_if(!$project, 404, 'Project not found');

        // Check if the project is deactivated
        abort_if($project->isDeactivated(), 403, 'This project has been deactivated and versions cannot be edited.');

        // Authorization check
        abort_unless(Gate::allows('editVersion', $project), 403, 'You do not have permission to edit versions of this project.');

        // Find the version
        $projectVersion = $project->versions()->where('version', $version)->first();

        abort_if(!$projectVersion, 404, 'Project version not found');

        // Validate request
        $validated = $request->validate($this->getUpdateValidationRules($project, $projectVersion, $request->input('dependencies', [])));

        // Prepare version data
        $versionData = [
            'name' => $validated['name'],
            'version' => $validated['version'],
            'release_type' => $validated['release_type'],
            'release_date' => $validated['release_date'],
            'changelog' => $validated['changelog'] ?? null,
        ];

        // Prepare dependencies - convert slugs to IDs
        $dependencies = $this->convertDependencySlugsToIds($validated['dependencies'] ?? []);

        // Prepare tags
        $tags = $validated['tags'] ?? [];

        // Convert version tag slugs to IDs
        $tags = !empty($tags)
            ? ProjectVersionTag::whereIn('slug', $tags)->pluck('id')->toArray()
            : [];

        // Prepare files
        $files = $validated['files'] ?? [];

        // Prepare existing files for the service
        $filesToRemove = $validated['files_to_remove'] ?? [];
        $cleanExistingFiles = $validated['clean_existing_files'] ?? false;

        // Build existing files array for the service
        $existingFiles = [];

        if ($cleanExistingFiles) {
            // Mark all existing files for deletion
            $existingFiles = $projectVersion->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'delete' => true,
                ];
            })->toArray();
        } elseif (!empty($filesToRemove)) {
            // Mark only specified files for deletion
            $existingFiles = $projectVersion->files
                ->whereIn('name', $filesToRemove)
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'delete' => true,
                    ];
                })->toArray();
        }

        try {
            // Update version using service
            $updatedVersion = $this->projectVersionService->saveVersion(
                $project,
                $versionData,
                $files,
                $existingFiles,
                $dependencies,
                $tags,
                $projectVersion
            );

            return ProjectVersionResource::make($updatedVersion->load(['tags','dependencies','files']))
                ->additional(['message' => 'Version updated successfully']);
        } catch (\Exception $e) {
            abort(500, 'Failed to update version: ' . $e->getMessage());
        }
    }

    /**
     * Delete a project version
     */
    #[HeaderParameter(name: 'Authorization', description: 'Bearer token', type: 'string', required: true, example: 'Bearer {token}')]
    #[PathParameter(name: 'slug', description: 'The project slug')]
    #[PathParameter(name: 'version', description: 'The project version')]
    public function destroy(Request $request, string $slug, string $version)
    {
        $project = Project::where('slug', $slug)->first();

        abort_if(!$project, 404, 'Project not found');

        // Authorization check
        abort_unless(Gate::allows('editVersion', $project), 403, 'You do not have permission to delete versions of this project.');

        // Find the version
        $projectVersion = $project->versions()->where('version', $version)->first();

        abort_if(!$projectVersion, 404, 'Project version not found');

        try {
            // Delete version using service
            $this->projectVersionService->deleteVersion($projectVersion, $project);

            return response()->noContent();
        } catch (\Exception $e) {
            abort(500, 'Failed to delete version: ' . $e->getMessage());
        }
    }

    /**
     * Get validation rules for creating a new version
     */
    private function getStoreValidationRules(Project $project, array $dependencies = []): array
    {
        $quotaLimits = $this->quotaService->getQuotaLimits(Auth::user(), $project->projectType, $project);

        $rules = [
            'name' => 'required|string|max:255',
            'version' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_.+-]+$/',
                Rule::unique(ProjectVersion::class, 'version')->where('project_id', $project->id),
            ],
            'release_type' => 'required|in:alpha,beta,rc,prerelease,release',
            'release_date' => 'required|date',
            'changelog' => 'nullable|string',
            'files' => [
                'required',
                'array',
                'min:1',
                'max:' . $quotaLimits['files_per_version_max'],
            ],
            'files.*' => [
                'file',
                'max:' . $quotaLimits['file_size_max'] / 1024,
            ],
            'dependencies' => 'nullable|array',
            'dependencies.*.type' => 'required|in:required,optional,embedded',
            'dependencies.*.external' => 'required|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:project_version_tag,slug',
        ];

        // Add dynamic validation for dependencies based on external flag
        $this->addDependencyValidationRules($rules, $dependencies ?? []);

        return $rules;
    }
    private function getUpdateValidationRules(Project $project, ProjectVersion $version, array $dependencies = []): array
    {
        $quotaLimits = $this->quotaService->getQuotaLimits(Auth::user(), $project->projectType, $project);

        $rules = [
            'name' => 'required|string|max:255',
            'version' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_.+-]+$/',
                Rule::unique(ProjectVersion::class, 'version')
                    ->where('project_id', $project->id)
                    ->ignore($version),
            ],
            'release_type' => 'required|in:alpha,beta,rc,prerelease,release',
            'release_date' => 'required|date',
            'changelog' => 'nullable|string',
            'files' => [
                'nullable',
                'array',
                'max:' . $quotaLimits['files_per_version_max'],
            ],
            'files.*' => [
                'file',
                'max:' . $quotaLimits['file_size_max'] / 1024,
            ],
            'files_to_remove' => 'nullable|array',
            'files_to_remove.*' => 'string|exists:project_file,name',
            'clean_existing_files' => 'nullable|boolean',
            'dependencies' => 'nullable|array',
            'dependencies.*.type' => 'required|in:required,optional,embedded',
            'dependencies.*.external' => 'required|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:project_version_tag,slug',
        ];

        // Add dynamic validation for dependencies based on external flag
        $this->addDependencyValidationRules($rules, $dependencies ?? []);

        return $rules;
    }

    /**
     * Add dynamic validation rules for dependencies based on their external flag
     */
    private function addDependencyValidationRules(array &$rules, array $dependencies): void
    {
        foreach ($dependencies as $index => $dependency) {
            if ($dependency['external'] === true) {
                // External/manual dependencies require dependency name and version fields
                $rules["dependencies.{$index}.project"] = 'required|string|max:255';
                $rules["dependencies.{$index}.version"] = 'nullable|string|max:50';
            } else {
                // Platform-linked dependencies require project slug validation
                $rules["dependencies.{$index}.project"] = 'required|exists:project,slug';
                $rules["dependencies.{$index}.version"] = 'nullable|exists:project_version,version';
            }
        }
    }

    /**
     * Convert dependency slugs to IDs for the service layer
     */
    private function convertDependencySlugsToIds(array $dependencies): array
    {
        if (empty($dependencies)) {
            return [];
        }

        $converted = [];

        foreach ($dependencies as $index => $dependency) {
            // Convert external flag to mode for service layer compatibility
            $mode = $dependency['external'] ? 'manual' : 'linked';

            $convertedDependency = [
                'type' => $dependency['type'],
                'mode' => $mode,
            ];

            // For platform-linked dependencies (external=false -> mode='linked')
            if (!$dependency['external']) {
                // Convert project slug to project_id
                if (!empty($dependency['project'])) {
                    $project = Project::where('slug', $dependency['project'])->first();
                    if ($project) {
                        $convertedDependency['project_id'] = $project->id;
                    }
                }

                // Convert version slug to version_id
                if (!empty($dependency['version'])) {
                    $projectIdForLookup = $convertedDependency['project_id'] ?? null;
                    $versionLookup = $dependency['version'];
                    $version = ProjectVersion::where('project_id', $projectIdForLookup)->where('version', $versionLookup)->first();
                    if ($version) {
                        $convertedDependency['version_id'] = $version->id;
                        $convertedDependency['has_specific_version'] = true;
                    } else {
                        $convertedDependency['has_specific_version'] = false;
                    }
                } else {
                    $convertedDependency['has_specific_version'] = false;
                }
            }
            // For external dependencies (external=true -> mode='manual')
            else {
                $convertedDependency['dependency_name'] = $dependency['project'] ?? null;
                $convertedDependency['dependency_version'] = $dependency['version'] ?? null;
                $convertedDependency['has_manual_version'] = !empty($dependency['version']);
            }

            $converted[] = $convertedDependency;
        }

        return $converted;
    }
}
