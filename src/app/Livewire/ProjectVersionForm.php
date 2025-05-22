<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDependency;
use App\Models\ProjectType;
use App\Models\ProjectVersionTag;
use App\Models\ProjectVersionTagGroup;
use App\Notifications\BrokenDependencyNotification;
use Barryvdh\Debugbar\Facades\Debugbar;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

// TODO: refactor the logic into a service
class ProjectVersionForm extends Component
{
    use WithFileUploads;

    public Project $project;
    public ?string $version_key = null;
    public ProjectVersion $version;
    public $isEditing = false;

    public $name = '';
    public $version_number = '';
    public $release_type = 'release';
    public $release_date;
    public $changelog = '';
    public $files = [];
    public $dependencies = [];
    public $existingFiles = [];
    public $deleteConfirmation = '';
    public $selectedTags = [];

    public function mount($projectType, $project, $version_key = null)
    {
        $this->project = $project;

        if (!Auth::check()) {
            return redirect()->route('login', ['projectType' => $projectType])
                ->with('error', 'Please log in to upload versions.');
        }

        if ($version_key) {
            if (!Gate::allows('editVersion', $project)) {
                return redirect()->route('project.show', ['projectType' => $projectType, 'project' => $project])
                    ->with('error', 'You do not have permission to edit this project.');
            }

            $this->version = $this->project->versions()->where('version', $version_key)->get()->first();
            $this->isEditing = true;

            $this->name = $this->version->name;
            $this->version_number = $this->version->version;
            $this->release_type = $this->version->release_type;
            $this->release_date = $this->version->release_date;
            $this->changelog = $this->version->changelog;

            $this->existingFiles = $this->version->files()->get()->toArray();

            $this->selectedTags = $this->version->tags()->pluck('tag_id')->toArray();

            $dependencies = $this->version->dependencies()->get();
            foreach ($dependencies as $dependency) {
                Debugbar::debug($dependency);

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
                        'has_manual_version' => false
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
                        'has_manual_version' => !empty($dependency->dependency_version) && $dependency->dependency_version !== 'Any'
                    ];
                }

                $this->dependencies[] = $dependencyData;
            }

        } else {

            if (!Gate::allows('uploadVersion', $project)) {
                return redirect()->route('project.show', ['projectType' => $projectType, 'project' => $project])
                    ->with('error', 'You do not have permission to upload versions.');
            }
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
            'has_manual_version' => false
        ];
    }

    public function removeDependency($index)
    {
        unset($this->dependencies[$index]);
        $this->dependencies = array_values($this->dependencies);
    }

    public function getVersionOptions($projectId)
    {
        return ProjectVersion::where('project_id', $projectId)
            ->orderBy('release_date', 'desc')
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->id,
                    'name' => $version->version . ' (' . $version->release_type . ')'
                ];
            })
            ->toArray();
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
            DB::beginTransaction();

            $projectVersion = $this->saveProjectVersion();

            $this->saveTags($projectVersion);

            if ($this->isEditing) {
                $this->deleteMarkedFiles($projectVersion);
            }

            $this->uploadNewFiles($projectVersion);

            $this->saveDependencies($projectVersion);

            DB::commit();

            return redirect()->route('project.version.show', [
                'projectType' => $this->project->projectType,
                'project' => $this->project,
                'version_key' => $projectVersion
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate all form data
     */
    private function validateForm()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'release_type' => 'required|in:alpha,beta,rc,release',
            'release_date' => 'required|date',
            'changelog' => 'nullable|string',
            'dependencies' => 'array',
            'dependencies.*.type' => 'required|in:required,optional,embedded',
            'dependencies.*.mode' => 'required|in:linked,manual'
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
            }
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
            'dependencies.*.dependency_version.max' => 'The version must not exceed 50 characters'
        ];

        $this->validate($rules, $messages);
    }

    /**
     * Add dependency-specific validation rules
     */
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

    /**
     * Add file validation rules
     */
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
                    }
                ];
            }
        }
    }

    /**
     * Create or update the project version
     *
     * @return ProjectVersion
     */
    private function saveProjectVersion()
    {
        $versionData = [
            'name' => $this->name,
            'version' => $this->version_number,
            'release_type' => $this->release_type,
            'release_date' => $this->release_date,
            'changelog' => $this->changelog
        ];

        if ($this->isEditing) {
            $this->version->update($versionData);
            $projectVersion = $this->version;

            $projectVersion->dependencies()->delete();
        } else {
            $projectVersion = $this->project->versions()->create($versionData);
        }

        return $projectVersion;
    }

    /**
     * Save tags for the project version
     *
     * @param ProjectVersion $projectVersion
     */
    private function saveTags(ProjectVersion $projectVersion)
    {
        if ($this->isEditing) {
            $projectVersion->tags()->sync($this->selectedTags);
        } elseif (!empty($this->selectedTags)) {
            $projectVersion->tags()->attach($this->selectedTags);
        }
    }

    /**
     * Delete files marked for deletion
     *
     * @param ProjectVersion $projectVersion
     */
    private function deleteMarkedFiles(ProjectVersion $projectVersion)
    {
        foreach ($this->existingFiles as $file) {
            if (isset($file['delete']) && $file['delete']) {
                $fileModel = $projectVersion->files()->find($file['id']);
                if ($fileModel) {
                    Storage::delete($fileModel->path);
                    $fileModel->delete();
                }
            }
        }
    }

    /**
     * Upload and save new files
     *
     * @param ProjectVersion $projectVersion
     */
    private function uploadNewFiles(ProjectVersion $projectVersion)
    {
        foreach ($this->files as $file) {
            $fileName = $file->getClientOriginalName();
            $path = $file->store('project-files');

            try {
                $projectVersion->files()->create([
                    'name' => $fileName,
                    'path' => $path,
                    'size' => $file->getSize()
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'project_file_unique')) {
                    Storage::delete($path);
                    throw new \Exception("A file with the name '{$fileName}' already exists in this version.");
                }
                throw $e;
            }
        }
    }

    /**
     * Save dependencies for the project version
     *
     * @param ProjectVersion $projectVersion
     */
    private function saveDependencies(ProjectVersion $projectVersion)
    {
        foreach ($this->dependencies as $dependency) {
            if ($dependency['mode'] === 'linked' && !empty($dependency['project_id'])) {
                $this->saveLinkedDependency($projectVersion, $dependency);
            } elseif ($dependency['mode'] === 'manual' && !empty($dependency['dependency_name'])) {
                $this->saveManualDependency($projectVersion, $dependency);
            }
        }
    }

    /**
     * Save a linked dependency
     *
     * @param ProjectVersion $projectVersion
     * @param array $dependency
     */
    private function saveLinkedDependency(ProjectVersion $projectVersion, array $dependency)
    {
        $dependencyData = [
            'project_version_id' => $projectVersion->id,
            'dependency_project_id' => $dependency['has_specific_version'] ? null : $dependency['project_id'],
            'dependency_project_version_id' => $dependency['has_specific_version'] ? $dependency['version_id'] : null,
            'dependency_type' => $dependency['type']
        ];

        if (!empty($dependency['dependency_name'])) {
            $dependencyData['dependency_name'] = $dependency['dependency_name'];
        }

        if (!empty($dependency['dependency_version'])) {
            $dependencyData['dependency_version'] = $dependency['dependency_version'];
        }

        ProjectVersionDependency::create($dependencyData);
    }

    /**
     * Save a manual dependency
     *
     * @param ProjectVersion $projectVersion
     * @param array $dependency
     */
    private function saveManualDependency(ProjectVersion $projectVersion, array $dependency)
    {
        ProjectVersionDependency::create([
            'project_version_id' => $projectVersion->id,
            'dependency_project_id' => null,
            'dependency_project_version_id' => null,
            'dependency_type' => $dependency['type'],
            'dependency_name' => $dependency['dependency_name'],
            'dependency_version' => $dependency['has_manual_version'] ? $dependency['dependency_version'] : 'Any'
        ]);
    }

    /**
     * Delete the version
     */
    public function deleteVersion()
    {
        if (!$this->isEditing) {
            return;
        }

        if (!Auth::check() || !Gate::allows('editVersion', $this->project)) {
            session()->flash('error', 'You do not have permission to delete this version.');
            return;
        }

        $this->validate([
            'deleteConfirmation' => 'required|in:' . $this->version->version,
        ], [
            'deleteConfirmation.in' => 'The version number you entered does not match. Please enter the exact version number to confirm deletion.'
        ]);

        try {
            DB::beginTransaction();

            $dependentVersions = $this->version->dependedOnBy()->with(['projectVersion.project.owner'])->get();

            $dependentProjects = [];
            foreach ($dependentVersions as $dependency) {
                if ($dependency->projectVersion && $dependency->projectVersion->project) {
                    $project = $dependency->projectVersion->project;
                    $version = $dependency->projectVersion;

                    if (!isset($dependentProjects[$project->id])) {
                        $dependentProjects[$project->id] = [
                            'project' => $project,
                            'versions' => []
                        ];
                    }

                    $dependentProjects[$project->id]['versions'][] = $version;
                }
            }

            foreach ($this->version->files as $file) {
                Storage::delete($file->path);
                $file->delete();
            }

            $this->version->dependencies()->delete();

            $this->version->delete();

            foreach ($dependentProjects as $projectData) {
                $project = $projectData['project'];
                $owners = $project->owner;

                foreach ($owners as $owner) {
                    foreach ($projectData['versions'] as $version) {
                        $owner->notify(new BrokenDependencyNotification(
                            $project,
                            $version,
                            $this->project->id,
                            $this->project->name,
                            $this->version->version,
                            Auth::user(),
                            false
                        ));
                    }
                }
            }

            DB::commit();

            return redirect()->route('project.show', [
                'projectType' => $this->project->projectType,
                'project' => $this->project
            ])->with('message', 'Version deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('error', 'Failed to delete version: ' . $e->getMessage());
            return;
        }
    }

    /**
     * Get available tags for the project type
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableTags()
    {
        $projectType = $this->project->projectType;
        $cacheKey = 'project_version_tags_by_type_' . $projectType->value;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($projectType) {
            return ProjectVersionTag::whereHas('projectTypes', function ($query) use ($projectType) {
                $query->where('project_type_id', $projectType->id);
            })->with('tagGroup')->get();
        });
    }

    /**
     * Get available tag groups for the project type
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableTagGroups()
    {
        $projectType = $this->project->projectType;
        $cacheKey = 'project_version_tag_groups_by_type_' . $projectType->value;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($projectType) {
            return ProjectVersionTagGroup::whereHas('projectTypes', function ($query) use ($projectType) {
                $query->where('project_type_id', $projectType->id);
            })->with('tags')->get();
        });
    }

    public function render()
    {
        return view('livewire.project-version-form', [
            'availableTags' => $this->getAvailableTags(),
            'availableTagGroups' => $this->getAvailableTagGroups(),
        ]);
    }
}
