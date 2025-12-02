<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectVersion;
use App\Services\ProjectVersionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

        if (!Auth::check()) {
            $this->error('Please log in to upload versions.', redirectTo: route('login', ['projectType' => $projectType]));
            return;
        }

        if ($version_key) {
            if (!Gate::allows('editVersion', $project)) {
                $this->error('You do not have permission to edit this project.', redirectTo: route('project.show', ['projectType' => $projectType, 'project' => $project]));
                return;
            }

            $this->version = $this->project->versions()->where('version', $version_key)->firstOrFail();
            $this->isEditing = true;

            $this->loadVersionData();
        } else {
            if (!Gate::allows('uploadVersion', $project)) {
                $this->error('You do not have permission to upload versions.', redirectTo: route('project.show', ['projectType' => $projectType, 'project' => $project]));
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
        // Handle Enum to string conversion
        $this->release_type = $this->version->release_type instanceof \App\Enums\ReleaseType 
            ? $this->version->release_type->value 
            : (string) $this->version->release_type;
            
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

    public function save()
    {
        $this->validateForm();

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

            $this->success(
                $this->isEditing ? 'Version updated successfully.' : 'Version uploaded successfully.',
                redirectTo: route('project.version.show', [
                    'projectType' => $this->project->projectType,
                    'project' => $this->project,
                    'version_key' => $projectVersion->version,
                ])
            );
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }

    private function validateForm()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'release_type' => 'required|in:alpha,beta,rc,release',
            'release_date' => 'required|date',
            'changelog' => 'nullable|string',
            'dependencies' => 'array',
            'dependencies.*.type' => 'required|in:required,optional,embedded',
            'dependencies.*.mode' => 'required|in:linked,manual',
        ];

        $rules['version_number'] = [
            'required',
            'string',
            'max:50',
            function ($attribute, $value, $fail) {
                $query = ProjectVersion::where('project_id', $this->project->id)
                    ->where('version', $value);

                if ($this->isEditing) {
                    $query->where('id', '!=', $this->version->id);
                }

                if ($query->exists()) {
                    $fail('This version number is already used in this project.');
                }
            },
        ];

        $this->addDependencyValidationRules($rules);
        $this->addFileValidationRules($rules);

        $messages = [
            'dependencies.*.project_id.required' => 'Please enter a valid project slug',
            'dependencies.*.project_slug.required' => 'The project slug field is required',
            'dependencies.*.version_id.required' => 'Please select a version',
            'dependencies.*.dependency_name.required' => 'The project name field is required',
            'dependencies.*.dependency_name.max' => 'The project name must not exceed 255 characters',
            'dependencies.*.dependency_version.required' => 'The version field is required',
            'dependencies.*.dependency_version.max' => 'The version must not exceed 50 characters',
        ];

        $this->validate($rules, $messages);
    }

    private function addDependencyValidationRules(&$rules)
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

    private function addFileValidationRules(&$rules)
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

    public function deleteVersion()
    {
        if (!$this->isEditing) {
            return;
        }

        if (!Auth::check() || !Gate::allows('editVersion', $this->project)) {
            $this->error('You do not have permission to delete this version.');
            return;
        }

        $this->validate([
            'deleteConfirmation' => 'required|in:' . $this->version->version,
        ], [
            'deleteConfirmation.in' => 'The version number you entered does not match. Please enter the exact version number to confirm deletion.',
        ]);

        try {
            $this->projectVersionService->deleteVersion($this->version, $this->project);

            $this->success('Version deleted successfully.', redirectTo: route('project.show', [
                'projectType' => $this->project->projectType,
                'project' => $this->project,
            ]));
        } catch (\Exception $e) {
            $this->error('Failed to delete version: ' . $e->getMessage());
        }
    }

    public function getAvailableTags()
    {
        return $this->projectVersionService->getAvailableTags($this->project->projectType);
    }

    public function getAvailableTagGroups()
    {
        return $this->projectVersionService->getAvailableTagGroups($this->project->projectType);
    }

    public function render()
    {
        return view('livewire.project-version-form', [
            'availableTags' => $this->getAvailableTags(),
            'availableTagGroups' => $this->getAvailableTagGroups(),
        ]);
    }
}
