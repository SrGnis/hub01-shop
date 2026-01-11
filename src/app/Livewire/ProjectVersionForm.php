<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectVersion;
use App\Services\ProjectVersionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class ProjectVersionForm extends Component
{
    use WithFileUploads;
    use Toast;

    public Project $project;
    public ?ProjectVersion $version = null;
    public bool $isEditing = false;

    public string $name = '';
    public string $version_number = '';
    public string $release_type = 'release';
    public $release_date;
    public string $changelog = '';

    // File uploads
    public $files = [];
    #[Locked]
    public $existingFiles = [];

    // Dependencies
    public $dependencies = [];

    // Tags
    public $selectedTags = [];

    // Delete confirmation
    public string $deleteConfirmation = '';

    protected ProjectVersionService $projectVersionService;

    public function boot(ProjectVersionService $projectVersionService)
    {
        $this->projectVersionService = $projectVersionService;
    }

    public function mount($projectType, Project $project, $version_key = null)
    {
        $this->project = $project;

        logger()->info('ProjectVersionForm mounted', [
            'project_id' => $project->id,
            'project_slug' => $project->slug,
            'version_key' => $version_key,
            'user_id' => Auth::id(),
        ]);

        if (! Auth::check()) {
            // use normal laravel flash message toast is not working here
            session()->flash('error', 'Please log in to upload versions.');
            return redirect()->route('login', ['projectType' => $projectType]);

            return;
        }

        // Check if the project is deactivated
        if ($project->isDeactivated()) {
            // use normal laravel flash message toast is not working here
            session()->flash('error', 'This project has been deactivated and versions cannot be created or edited.');
            return redirect()->route('project.show', ['projectType' => $projectType, 'project' => $project]);

            return;
        }

        if ($version_key) {
            if (! Gate::allows('editVersion', $project)) {
                logger()->warning('Unauthorized version edit attempt', [
                    'project_id' => $project->id,
                    'version_key' => $version_key,
                    'user_id' => Auth::id(),
                ]);

                session()->flash('error', 'You do not have permission to edit this project.');
                return redirect()->route('project.show', ['projectType' => $projectType, 'project' => $project]);

                return;
            }

            $this->version = $this->project->versions()->where('version', $version_key)->firstOrFail();
            $this->isEditing = true;

            logger()->info('Editing existing version', [
                'project_id' => $project->id,
                'version_id' => $this->version->id,
                'version_number' => $this->version->version,
            ]);

            $this->loadVersionData();
        } else {
            if (! Gate::allows('uploadVersion', $project)) {
                logger()->warning('Unauthorized version upload attempt', [
                    'project_id' => $project->id,
                    'user_id' => Auth::id(),
                ]);

                session()->flash('error', 'You do not have permission to upload versions.');
                return redirect()->route('project.show', ['projectType' => $projectType, 'project' => $project]);

                return;
            }

            // Default release date to today
            $this->release_date = now()->format('Y-m-d');
        }
    }

    private function loadVersionData()
    {
        $this->name = $this->version->name;
        $this->version_number = $this->version->version;

        $this->release_type = $this->version->release_type->value;

        $this->release_date = $this->version->release_date->format('Y-m-d');
        $this->changelog = $this->version->changelog ?? '';

        $this->existingFiles = $this->version->files()->get()->toArray();
        $this->selectedTags = $this->version->tags()->pluck('tag_id')->toArray();

        $this->loadDependencies();
    }

    private function loadDependencies()
    {
        $dependencies = $this->version->dependencies()->get();
        foreach ($dependencies as $dependency) {
            if ($dependency->dependency_project_id || $dependency->dependency_project_version_id) {
                $dependencyData = [
                    'type' => $dependency->dependency_type,
                    'mode' => 'linked',
                    'project_slug' => '',
                    'project_id' => null,
                    'has_specific_version' => false,
                    'version_id' => null,
                    'dependency_name' => $dependency->dependency_name,
                    'dependency_version' => $dependency->dependency_version,
                    'has_manual_version' => false,
                ];

                if ($dependency->dependency_project_id) {
                    $project = Project::find($dependency->dependency_project_id);
                    if ($project) {
                        $dependencyData['project_slug'] = $project->slug;
                        $dependencyData['project_id'] = $project->id;
                    }
                } elseif ($dependency->dependency_project_version_id) {
                    $projectVersion = ProjectVersion::with('project')->find($dependency->dependency_project_version_id);
                    if ($projectVersion && $projectVersion->project) {
                        $dependencyData['project_slug'] = $projectVersion->project->slug;
                        $dependencyData['project_id'] = $projectVersion->project->id;
                        $dependencyData['has_specific_version'] = true;
                        $dependencyData['version_id'] = $projectVersion->id;
                    }
                }
            } else {
                $dependencyData = [
                    'type' => $dependency->dependency_type,
                    'mode' => 'manual',
                    'project_slug' => '',
                    'project_id' => null,
                    'has_specific_version' => false,
                    'version_id' => null,
                    'dependency_name' => $dependency->dependency_name,
                    'dependency_version' => $dependency->dependency_version,
                    'has_manual_version' => !empty($dependency->dependency_version) && $dependency->dependency_version !== 'Any',
                ];
            }

            $this->dependencies[] = $dependencyData;
        }
    }

    public function addDependency()
    {
        $this->dependencies[] = [
            'type' => 'required',
            'mode' => 'linked',
            'project_slug' => '',
            'project_id' => null,
            'has_specific_version' => false,
            'version_id' => null,
            'dependency_name' => '',
            'dependency_version' => '',
            'has_manual_version' => false,
        ];
    }

    public function removeDependency($index)
    {
        unset($this->dependencies[$index]);
        $this->dependencies = array_values($this->dependencies);
    }

    public function getVersionOptions($projectId)
    {
        return $this->projectVersionService->getVersionOptions($projectId);
    }

    public function updatedDependencies($value, $key)
    {
        if ($key && preg_match('/^(\d+)\.project_slug$/', $key, $matches)) {
            $index = $matches[1];
            $this->validateProjectSlug($index, $value);
        }

        if ($key && preg_match('/^(\d+)\.mode$/', $key, $matches)) {
            $index = $matches[1];
            if ($value === 'linked') {
                $this->dependencies[$index]['dependency_name'] = '';
                $this->dependencies[$index]['dependency_version'] = '';
                $this->dependencies[$index]['has_manual_version'] = false;
            } else { // manual mode
                $this->dependencies[$index]['project_slug'] = '';
                $this->dependencies[$index]['project_id'] = null;
                $this->dependencies[$index]['has_specific_version'] = false;
                $this->dependencies[$index]['version_id'] = null;
            }
        }
    }

    public function validateProjectSlug($index, $slug)
    {
        $this->dependencies[$index]['project_id'] = null;

        $slug = trim($slug);

        if (empty($slug)) {
            return;
        }

        $project = Project::where('slug', $slug)
            ->where('id', '!=', $this->project->id)
            ->first();

        if ($project) {
            $this->dependencies[$index]['project_id'] = $project->id;
            return true;
        }

        return false;
    }

    public function removeExistingFile($fileId)
    {
        foreach ($this->existingFiles as $index => $file) {
            if ($file['id'] == $fileId) {
                $this->existingFiles[$index]['delete'] = true;
                break;
            }
        }
    }

    public function removeNewFile($index)
    {
        // Remove the file at the specified index and re-index the array
        if (isset($this->files[$index])) {
            unset($this->files[$index]);
            $this->files = array_values($this->files); // Re-index array to avoid gaps
        }
    }

    public function save()
    {
        logger()->info('Saving project version', [
            'project_id' => $this->project->id,
            'is_editing' => $this->isEditing,
            'version_id' => $this->version?->id,
            'version_number' => $this->version_number,
            'user_id' => Auth::id(),
        ]);

        $this->validate();

        try {
            $versionData = [
                'name' => $this->name,
                'version' => $this->version_number,
                'release_type' => $this->release_type,
                'release_date' => $this->release_date,
                'changelog' => $this->changelog,
            ];

            $projectVersion = $this->projectVersionService->saveVersion(
                $this->project,
                $versionData,
                $this->files,
                $this->existingFiles,
                $this->dependencies,
                $this->selectedTags,
                $this->version
            );

            logger()->info('Project version saved successfully', [
                'project_id' => $this->project->id,
                'version_id' => $projectVersion->id,
                'version_number' => $projectVersion->version,
                'user_id' => Auth::id(),
            ]);

            $this->success(
                $this->isEditing ? 'Version updated successfully.' : 'Version uploaded successfully.',
                redirectTo: route('project.version.show', [
                    'projectType' => $this->project->projectType,
                    'project' => $this->project,
                    'version_key' => $projectVersion->version,
                ])
            );
        } catch (\Exception $e) {
            logger()->error('Error saving project version: ' . $e->getMessage(), [
                'project_id' => $this->project->id,
                'version_id' => $this->version?->id,
                'version_number' => $this->version_number,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Validation rules
     *
     * @return array<string, string|array>
     */
    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'release_type' => 'required|in:alpha,beta,rc,release',
            'release_date' => 'required|date',
            'changelog' => 'nullable|string',
            'dependencies' => 'array',
            'dependencies.*.type' => 'required|in:required,optional,embedded',
            'dependencies.*.mode' => 'required|in:linked,manual',
            'selectedTags' => [
                'array',
                function ($attribute, $value, $fail) {
                    $this->validateTagsForProjectType($value, $fail);
                },
            ],
        ];

        $uniqueVersionRule = Rule::unique(ProjectVersion::class, 'version')
            ->where('project_id', $this->project->id);

        if ($this->isEditing) {
            $uniqueVersionRule->ignore($this->version);
        }

        $rules['version_number'] = [
            'required',
            'string',
            'max:50',
            $uniqueVersionRule,
        ];

        $this->addDependencyValidationRules($rules);
        $this->addFileValidationRules($rules);


        return $rules;
    }

    /**
     * Validate that all selected tags belong to tag groups valid for the current project type.
     */
    private function validateTagsForProjectType(array $selectedTagIds, callable $fail): void
    {
        if (empty($selectedTagIds)) {
            return;
        }

        $invalidTags = \App\Models\ProjectVersionTag::whereIn('id', $selectedTagIds)
            ->whereDoesntHave('projectTypes', fn ($query) => $query->where('project_type_id', $this->project->projectType->id))
            ->pluck('name')
            ->toArray();

        if (!empty($invalidTags)) {
            $tagNames = implode(', ', $invalidTags);
            $fail("The following tags are not allowed for this project type: {$tagNames}.");
        }
    }

    /**
     * Add dynamic validation rules for dependencies based on their mode.
     *
     * For linked dependencies (referencing existing projects in the hub):
     * - Requires a valid project_id and project_slug
     * - Optionally requires a specific version_id if has_specific_version is true
     * - dependency_name and dependency_version are optional (derived from linked project)
     *
     * For manual dependencies (external projects not in the hub):
     * - Requires a dependency_name
     * - Requires dependency_version only if has_manual_version is true
     *
     * @param  array<string, mixed>  $rules  The validation rules array to modify by reference
     */
    private function addDependencyValidationRules(array &$rules): void
    {
        foreach ($this->dependencies as $index => $dependency) {
            if ($dependency['mode'] === 'linked') {
                $rules["dependencies.{$index}.project_id"] = 'required|exists:project,id';
                $rules["dependencies.{$index}.project_slug"] = 'required|string';

                if (!empty($dependency['project_id']) && $dependency['has_specific_version']) {
                    $rules["dependencies.{$index}.version_id"] = 'required|exists:project_version,id';
                }

                $rules["dependencies.{$index}.dependency_name"] = 'nullable|string|max:255';
                $rules["dependencies.{$index}.dependency_version"] = 'nullable|string|max:50';
            } else { // manual mode
                $rules["dependencies.{$index}.dependency_name"] = 'required|string|max:255';
                $rules["dependencies.{$index}.dependency_version"] = $dependency['has_manual_version']
                    ? 'required|string|max:50'
                    : 'nullable|string|max:50';
            }
        }
    }

    /**
     * Add dynamic validation rules for file uploads.
     *
     * Validates that:
     * - At least one file is uploaded when creating a new version
     * - Each file does not exceed 100MB (102400 KB)
     * - File names are unique within the version (no duplicates with existing files)
     * - File names are unique within the current upload batch
     *
     * @param  array<string, mixed>  $rules  The validation rules array to modify by reference
     */
    private function addFileValidationRules(array &$rules): void
    {
        if (!$this->isEditing || count($this->files) > 0) {
            $rules['files'] = 'required|array|min:1';
            $rules['files.*'] = 'file|max:102400';

            foreach ($this->files as $index => $file) {
                $rules["files.{$index}"] = [
                    'file',
                    'max:102400',
                    function ($attribute, $value, $fail) use ($file) {
                        $fileName = $file->getClientOriginalName();

                        if ($this->isEditing) {
                            foreach ($this->existingFiles as $existingFile) {
                                if (isset($existingFile['delete']) && $existingFile['delete']) {
                                    continue;
                                }

                                if ($existingFile['name'] === $fileName) {
                                    $fail("A file with the name '{$fileName}' already exists in this version.");
                                    return;
                                }
                            }
                        }

                        $count = 0;
                        foreach ($this->files as $uploadFile) {
                            if ($uploadFile->getClientOriginalName() === $fileName) {
                                $count++;
                            }
                        }

                        if ($count > 1) {
                            $fail("Duplicate file name '{$fileName}' in the upload batch. File names must be unique.");
                        }
                    },
                ];
            }
        }
    }

    /**
     * Custom validation error messages for dependency fields.
     *
     * Provides user-friendly error messages for:
     * - Invalid or missing project slugs in linked dependencies
     * - Missing version selection when specific version is required
     * - Missing or invalid dependency name/version in manual dependencies
     *
     * @return array<string, string>  Validation attribute => error message pairs
     */
    public function messages(): array
    {
        return [
            'dependencies.*.project_id.required' => 'Please enter a valid project slug',
            'dependencies.*.project_slug.required' => 'The project slug field is required',
            'dependencies.*.version_id.required' => 'Please select a version',
            'dependencies.*.dependency_name.required' => 'The project name field is required',
            'dependencies.*.dependency_name.max' => 'The project name must not exceed 255 characters',
            'dependencies.*.dependency_version.required' => 'The version field is required',
            'dependencies.*.dependency_version.max' => 'The version must not exceed 50 characters',
        ];
    }

    public function deleteVersion()
    {
        if (!$this->isEditing) {
            return;
        }

        if (!Auth::check() || !Gate::allows('editVersion', $this->project)) {
            logger()->warning('Unauthorized version deletion attempt', [
                'project_id' => $this->project->id,
                'version_id' => $this->version?->id,
                'user_id' => Auth::id(),
            ]);
            $this->error('You do not have permission to delete this version.');
            return;
        }

        $this->validate([
            'deleteConfirmation' => 'required|in:' . $this->version->version,
        ], [
            'deleteConfirmation.in' => 'The version number you entered does not match. Please enter the exact version number to confirm deletion.',
        ]);

        logger()->info('Deleting project version', [
            'project_id' => $this->project->id,
            'version_id' => $this->version->id,
            'version_number' => $this->version->version,
            'user_id' => Auth::id(),
        ]);

        try {
            $this->projectVersionService->deleteVersion($this->version, $this->project);

            logger()->info('Project version deleted successfully', [
                'project_id' => $this->project->id,
                'version_id' => $this->version->id,
                'version_number' => $this->version->version,
                'user_id' => Auth::id(),
            ]);

            $this->success('Version deleted successfully.', redirectTo: route('project.show', [
                'projectType' => $this->project->projectType,
                'project' => $this->project,
            ]));
        } catch (\Exception $e) {
            logger()->error('Failed to delete version: ' . $e->getMessage(), [
                'project_id' => $this->project->id,
                'version_id' => $this->version->id,
                'version_number' => $this->version->version,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);
            $this->error('Failed to delete version: ' . $e->getMessage());
        }
    }

    public function updatedVersionNumber()
    {
        $this->resetValidation('version_number');
        $version_number_rules = $this->rules()['version_number'];
        $this->validate(['version_number' => $version_number_rules]);
    }

    // dummy method for attaching the loading state
    public function refreshMarkdown(): void {}

    #[Computed]
    public function availableTags()
    {
        return $this->projectVersionService->getAvailableTags($this->project->projectType);
    }

    #[Computed]
    public function availableTagGroups()
    {
        return $this->projectVersionService->getAvailableTagGroups($this->project->projectType);
    }

    public function render()
    {
        return view('livewire.project-version-form', [
        ]);
    }
}
