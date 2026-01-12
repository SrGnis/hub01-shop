<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDependency;
use App\Models\ProjectVersionTag;
use App\Models\ProjectVersionTagGroup;
use App\Models\ProjectType;
use App\Notifications\BrokenDependencyNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectVersionService
{
    protected ProjectQuotaService $quotaService;

    public function __construct(ProjectQuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    /**
     * Create or update a project version
     */
    public function saveVersion(Project $project, array $data, array $files, array $existingFiles, array $dependencies, array $tags, ?ProjectVersion $version = null): ProjectVersion
    {

        // Calculate total size of new files
        $newFilesSize = 0;
        foreach ($files as $file) {
            $newFilesSize += $file->getSize();
        }

        // Calculate size of files being deleted (for edits)
        $deletedFilesSize = 0;
        if ($version) {
            foreach ($existingFiles as $file) {
                if (isset($file['delete']) && $file['delete']) {
                    $deletedFilesSize += $file['size'] ?? 0;
                }
            }
        }

        // Net size change (new files - deleted files)
        $netSizeChange = $newFilesSize - $deletedFilesSize;

        // Validate quotas before proceeding
        $this->quotaService->validateVersionUpload(
            $project->owner->first(),
            $project,
            $netSizeChange,
            !$version // isNewVersion
        );

        return DB::transaction(function () use ($project, $data, $files, $existingFiles, $dependencies, $tags, $version) {
            if ($version) {
                $version->update($data);
                $projectVersion = $version;
                $projectVersion->dependencies()->delete();

                $this->deleteMarkedFiles($projectVersion, $existingFiles);
            } else {
                $projectVersion = $project->versions()->create($data);
            }

            $this->saveTags($projectVersion, $tags, $version ? true : false);
            $this->uploadNewFiles($projectVersion, $files, $version ? true : false, $existingFiles);
            $this->saveDependencies($projectVersion, $dependencies);

            Log::info('Project version saved', [
                'version_id' => $projectVersion->id,
                'project_id' => $project->id,
                'version' => $projectVersion->version,
                'is_new' => !$version,
            ]);

            return $projectVersion;
        });
    }

    /**
     * Save tags for the project version
     */
    private function saveTags(ProjectVersion $projectVersion, array $tags, bool $isEditing)
    {
        if ($isEditing) {
            $projectVersion->tags()->sync($tags);
        } elseif (!empty($tags)) {
            $projectVersion->tags()->attach($tags);
        }
    }

    /**
     * Delete files marked for deletion
     */
    private function deleteMarkedFiles(ProjectVersion $projectVersion, array $existingFiles)
    {
        foreach ($existingFiles as $file) {
            if (isset($file['delete']) && $file['delete']) {
                $fileModel = $projectVersion->files()->find($file['id']);
                if ($fileModel) {
                    Storage::disk(ProjectFile::getDisk())->delete($fileModel->path);
                    $fileModel->delete();
                }
            }
        }
    }

    /**
     * Upload and save new files
     */
    private function uploadNewFiles(ProjectVersion $projectVersion, array $files, bool $isEditing, array $existingFiles)
    {
        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();

            // Double check for duplicates if editing, though validation should catch this
            if ($isEditing) {
                foreach ($existingFiles as $existingFile) {
                    if (!isset($existingFile['delete']) || !$existingFile['delete']) {
                        if ($existingFile['name'] === $fileName) {
                            throw new \Exception("A file with the name '{$fileName}' already exists in this version.");
                        }
                    }
                }
            }

            $path = $file->store(ProjectFile::getDirectory(), ProjectFile::getDisk());

            try {
                $projectVersion->files()->create([
                    'name' => $fileName,
                    'path' => $path,
                    'size' => $file->getSize(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'project_file_unique')) {
                    Storage::disk(ProjectFile::getDisk())->delete($path);
                    throw new \Exception("A file with the name '{$fileName}' already exists in this version.");
                }
                throw $e;
            }
        }
    }

    /**
     * Save dependencies for the project version
     */
    private function saveDependencies(ProjectVersion $projectVersion, array $dependencies)
    {
        foreach ($dependencies as $dependency) {
            if ($dependency['mode'] === 'linked' && !empty($dependency['project_id'])) {
                $this->saveLinkedDependency($projectVersion, $dependency);
            } elseif ($dependency['mode'] === 'manual' && !empty($dependency['dependency_name'])) {
                $this->saveManualDependency($projectVersion, $dependency);
            }
        }
    }

    /**
     * Save a linked dependency
     */
    private function saveLinkedDependency(ProjectVersion $projectVersion, array $dependency)
    {
        $dependencyData = [
            'project_version_id' => $projectVersion->id,
            'dependency_project_id' => $dependency['has_specific_version'] ? null : $dependency['project_id'],
            'dependency_project_version_id' => $dependency['has_specific_version'] ? $dependency['version_id'] : null,
            'dependency_type' => $dependency['type'],
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
     */
    private function saveManualDependency(ProjectVersion $projectVersion, array $dependency)
    {
        ProjectVersionDependency::create([
            'project_version_id' => $projectVersion->id,
            'dependency_project_id' => null,
            'dependency_project_version_id' => null,
            'dependency_type' => $dependency['type'],
            'dependency_name' => $dependency['dependency_name'],
            'dependency_version' => $dependency['has_manual_version'] ? $dependency['dependency_version'] : 'Any',
        ]);
    }

    /**
     * Delete a version
     */
    public function deleteVersion(ProjectVersion $version, Project $project)
    {
        return DB::transaction(function () use ($version, $project) {
            $dependentVersions = $version->dependedOnBy()->with(['projectVersion.project.owner'])->get();

            $dependentProjects = [];
            foreach ($dependentVersions as $dependency) {
                if ($dependency->projectVersion && $dependency->projectVersion->project) {
                    $depProject = $dependency->projectVersion->project;
                    $depVersion = $dependency->projectVersion;

                    if (!isset($dependentProjects[$depProject->id])) {
                        $dependentProjects[$depProject->id] = [
                            'project' => $depProject,
                            'versions' => [],
                        ];
                    }

                    $dependentProjects[$depProject->id]['versions'][] = $depVersion;
                }
            }

            foreach ($version->files as $file) {
                Storage::disk(ProjectFile::getDisk())->delete($file->path);
                $file->delete();
            }

            $version->dependencies()->delete();
            $version->delete();

            Log::info('Project version deleted', [
                'version_id' => $version->id,
                'project_id' => $project->id,
                'version' => $version->version,
            ]);

            foreach ($dependentProjects as $projectData) {
                $depProject = $projectData['project'];
                $owners = $depProject->owner;

                foreach ($owners as $owner) {
                    foreach ($projectData['versions'] as $depVersion) {
                        $owner->notify(new BrokenDependencyNotification(
                            $depProject,
                            $depVersion,
                            $project->id,
                            $project->name,
                            $version->version,
                            Auth::user(),
                            false
                        ));
                    }
                }
            }
        });
    }

    /**
     * Get available tags for the project type
     */
    public function getAvailableTags(ProjectType $projectType)
    {
        $cacheKey = 'project_version_tags_by_type_' . $projectType->value;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($projectType) {
            return ProjectVersionTag::whereHas('projectTypes', function ($query) use ($projectType) {
                $query->where('project_type_id', $projectType->id);
            })->with('tagGroup')->get();
        });
    }

    /**
     * Get available tag groups for the project type
     */
    public function getAvailableTagGroups(ProjectType $projectType)
    {
        $cacheKey = 'project_version_tag_groups_by_type_' . $projectType->value;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($projectType) {
            return ProjectVersionTagGroup::whereHas('projectTypes', function ($query) use ($projectType) {
                $query->where('project_type_id', $projectType->id);
            })->with('tags')->get();
        });
    }

    /**
     * Get version options for a project
     */
    public function getVersionOptions($projectId)
    {
        return ProjectVersion::where('project_id', $projectId)
            ->orderBy('release_date', 'desc')
            ->get()
            ->map(function ($version) {
                $releaseType = $version->release_type instanceof \App\Enums\ReleaseType
                    ? $version->release_type->value
                    : (string) $version->release_type;

                return [
                    'id' => $version->id,
                    'name' => $version->version . ' (' . $releaseType . ')',
                ];
            })
            ->toArray();
    }
}
