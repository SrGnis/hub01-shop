<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectTagGroup;
use App\Models\ProjectType;
use App\Models\ProjectVersionTagGroup;
use App\Models\User;
use App\Notifications\BrokenDependencyNotification;
use App\Notifications\MembershipInvitation;
use App\Notifications\MembershipRemoved;
use App\Notifications\PrimaryStatusChanged;
use App\Notifications\ProjectDeleted;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
    /**
     * Search and filter projects based on criteria
     */
    public function searchProjects(
        ProjectType $projectType,
        string $search = '',
        array $selectedTags = [],
        array $selectedVersionTags = [],
        string $orderBy = 'downloads',
        string $orderDirection = 'desc',
        int $resultsPerPage = 10
    ): LengthAwarePaginator {
        $projects = Project::where('name', 'like', '%' . $search . '%')
            ->where('project_type_id', $projectType->id);

        // Filter by project tags
        if (count($selectedTags)) {
            $projects->whereHas('tags', function ($query) use ($selectedTags) {
                $query->whereIn('tag_id', $selectedTags);
            }, '>=', count($selectedTags));
        }

        // Filter by version tags
        if (count($selectedVersionTags)) {
            $projects->whereHas('versions.tags', function ($query) use ($selectedVersionTags) {
                $query->whereIn('tag_id', $selectedVersionTags);
            });
        }

        // Apply ordering based on selected option
        $projects = $this->applyOrdering($projects, $orderBy, $orderDirection);

        return $projects->paginate($resultsPerPage);
    }

    /**
     * Apply ordering to the query
     */
    private function applyOrdering(Builder $query, string $orderBy, string $orderDirection): Builder
    {
        switch ($orderBy) {
            case 'name':
                return $query->orderBy('name', $orderDirection);
            case 'created_at':
                return $query->orderBy('created_at', $orderDirection);
            case 'latest_version':
                return $query->orderBy('recent_release_date', $orderDirection);
            case 'downloads':
            default:
                return $query->orderBy('downloads', $orderDirection);
        }
    }

    /**
     * Get tag groups for a project type with caching
     */
    public function getTagGroups(ProjectType $projectType): Collection
    {
        $cacheKey = 'project_tag_groups_by_type_' . $projectType->value;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($projectType) {
            return ProjectTagGroup::whereHas('projectTypes', function ($query) use ($projectType) {
                $query->where('project_type_id', $projectType->id);
            })->with(['tags' => function ($query) use ($projectType) {
                $query->whereHas('projectTypes', function ($subQuery) use ($projectType) {
                    $subQuery->where('project_type_id', $projectType->id);
                });
            }])->get();
        });
    }

    /**
     * Get version tag groups for a project type with caching
     */
    public function getVersionTagGroups(ProjectType $projectType): Collection
    {
        $cacheKey = 'project_version_tag_groups_by_type_' . $projectType->value;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($projectType) {
            return ProjectVersionTagGroup::whereHas('projectTypes', function ($query) use ($projectType) {
                $query->where('project_type_id', $projectType->id);
            })->with(['tags' => function ($query) use ($projectType) {
                $query->whereHas('projectTypes', function ($subQuery) use ($projectType) {
                    $subQuery->where('project_type_id', $projectType->id);
                });
            }])->get();
        });
    }

    /**
     * Get order options for the search interface
     */
    public function getOrderOptions(): array
    {
        return [
            ['id' => 'name', 'name' => 'Project Name', 'icon' => 'lucide-text'],
            ['id' => 'created_at', 'name' => 'Creation Date', 'icon' => 'lucide-calendar'],
            ['id' => 'latest_version', 'name' => 'Update Date', 'icon' => 'lucide-refresh-cw'],
            ['id' => 'downloads', 'name' => 'Downloads', 'icon' => 'lucide-download'],
        ];
    }

    /**
     * Get direction options for the search interface
     */
    public function getDirectionOptions(): array
    {
        return [
            ['id' => 'asc', 'name' => 'Ascending', 'icon' => 'lucide-arrow-up'],
            ['id' => 'desc', 'name' => 'Descending', 'icon' => 'lucide-arrow-down'],
        ];
    }

    /**
     * Get per page options for the search interface
     */
    public function getPerPageOptions(): array
    {
        return [
            ['id' => 10, 'name' => '10'],
            ['id' => 25, 'name' => '25'],
            ['id' => 50, 'name' => '50'],
            ['id' => 100, 'name' => '100'],
        ];
    }

    /**
     * Generate a slug for a project
     */
    public function generateSlug(string $name, ?Project $project = null): string
    {
        if ($project) {
            return $project->generateSlug($name);
        }

        $tempProject = new Project();
        return $tempProject->generateSlug($name);
    }

    /**
     * Get tags for a project type
     */
    public function getTagsForProjectType(ProjectType $projectType): Collection
    {
        return ProjectTag::whereHas('projectTypes', function ($query) use ($projectType) {
            $query->where('project_type_id', $projectType->id);
        })->with('tagGroup')->get();
    }

    /**
     * Get tag groups for a project type
     */
    public function getTagGroupsForProjectType(ProjectType $projectType): Collection
    {
        return ProjectTagGroup::whereHas('projectTypes', function ($query) use ($projectType) {
            $query->where('project_type_id', $projectType->id);
        })->with(['tags' => function ($query) use ($projectType) {
            $query->whereHas('projectTypes', function ($subQuery) use ($projectType) {
                $subQuery->where('project_type_id', $projectType->id);
            });
        }])->get();
    }

    /**
     * Save or update a project
     *
     * @param  Project|null  $project  The project to update, or null for new project
     * @param  array  $data  The project data
     * @param  string|null  $logoPath  The new logo path, empty string to remove, null to keep existing
     */
    public function saveProject(
        ?Project $project,
        array $data,
        ?string $logoPath = null
    ): Project {
        if ($project && $project->exists) {
            // Update existing project
            if ($logoPath !== null) {
                // New logo uploaded or logo removal requested
                if ($project->logo_path) {
                    Storage::disk('public')->delete($project->logo_path);
                }
                // If logoPath is empty string, set to null (removal)
                $logoPath = $logoPath === '' ? null : $logoPath;
            } else {
                // Keep existing logo
                $logoPath = $project->logo_path;
            }

            $project->update(array_merge($data, ['logo_path' => $logoPath]));
            $project->tags()->sync($data['selectedTags'] ?? []);

            return $project;
        }

        // Create new project
        $project = Project::create(array_merge($data, ['logo_path' => $logoPath]));
        $project->tags()->attach($data['selectedTags'] ?? []);

        // Create owner membership
        $membership = new Membership([
            'role' => 'owner',
            'primary' => true,
        ]);
        $membership->user()->associate(Auth::user());
        $membership->project()->associate($project);
        $membership->save();

        return $project;
    }

    /**
     * Add a member to a project
     */
    public function addMember(Project $project, string $userName, string $role): void
    {
        $user = User::where('name', $userName)->firstOrFail();

        if ($project->users()->where('user_id', $user->id)->exists()) {
            throw new \Exception('This user is already a member of the project.');
        }

        $membership = new Membership([
            'role' => $role,
            'primary' => false,
            'status' => 'pending',
        ]);
        $membership->user()->associate($user);
        $membership->project()->associate($project);
        $membership->inviter()->associate(Auth::user());
        $membership->save();

        $user->notify(new MembershipInvitation($membership));
    }

    /**
     * Remove a member from a project
     */
    public function removeMember(Project $project, int $membershipId): bool
    {
        $membership = Membership::findOrFail($membershipId);

        if ($membership->project_id !== $project->id) {
            throw new \Exception('Invalid membership.');
        }

        if ($membership->user_id === Auth::id() && $membership->primary) {
            throw new \Exception('You cannot remove yourself as the primary owner. Transfer ownership to another member first.');
        }

        if ($membership->primary && $project->memberships()->where('primary', true)->count() <= 1) {
            throw new \Exception('You cannot remove the last primary member of the project.');
        }

        $isSelfRemoval = $membership->user_id === Auth::id();
        $removedUser = User::find($membership->user_id);
        $currentUser = Auth::user();

        DB::beginTransaction();

        try {
            $projectMembers = $project->active_users()->get();
            $membership->delete();

            foreach ($projectMembers as $member) {
                $member->notify(new MembershipRemoved($project, $removedUser, $currentUser, $isSelfRemoval));
            }

            DB::commit();

            return $isSelfRemoval;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Set a member as primary owner
     */
    public function setPrimaryMember(Project $project, int $membershipId): void
    {
        $membership = Membership::findOrFail($membershipId);

        if ($membership->project_id !== $project->id) {
            throw new \Exception('Invalid membership.');
        }

        if ($membership->status !== 'active') {
            throw new \Exception('Only active members can be set as primary.');
        }

        if ($membership->primary) {
            throw new \Exception('This member is already a primary member.');
        }

        if (!$project->memberships()->where('user_id', Auth::id())->where('primary', true)->exists()) {
            throw new \Exception('Only primary owners can transfer ownership.');
        }

        DB::beginTransaction();

        try {
            $project->memberships()
                ->where('id', '!=', $membership->id)
                ->where('primary', true)
                ->update(['primary' => false]);

            $membership->update(['primary' => true]);

            $newPrimaryUser = User::find($membership->user_id);
            $currentUser = Auth::user();
            $projectMembers = $project->active_users()->get();

            foreach ($projectMembers as $member) {
                $member->notify(new PrimaryStatusChanged($project, $newPrimaryUser, $currentUser));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a project
     */
    public function deleteProject(Project $project): void
    {
        DB::beginTransaction();

        try {
            $dependentProjectVersions = $project->dependedOnBy()->with(['projectVersion.project.owner'])->get();
            $projectVersionIds = $project->versions()->pluck('id')->toArray();
            $dependentVersions = \App\Models\ProjectVersionDependency::whereIn('dependency_project_version_id', $projectVersionIds)
                ->with(['projectVersion.project.owner'])
                ->get();
            $dependentProjects = [];

            foreach ($dependentProjectVersions as $dependency) {
                if ($dependency->projectVersion && $dependency->projectVersion->project) {
                    $project_data = $dependency->projectVersion->project;
                    $version = $dependency->projectVersion;

                    if (!isset($dependentProjects[$project_data->id])) {
                        $dependentProjects[$project_data->id] = [
                            'project' => $project_data,
                            'versions' => [],
                        ];
                    }

                    $dependentProjects[$project_data->id]['versions'][] = $version;
                }
            }

            foreach ($dependentVersions as $dependency) {
                if ($dependency->projectVersion && $dependency->projectVersion->project) {
                    $project_data = $dependency->projectVersion->project;
                    $version = $dependency->projectVersion;

                    if (!isset($dependentProjects[$project_data->id])) {
                        $dependentProjects[$project_data->id] = [
                            'project' => $project_data,
                            'versions' => [],
                        ];
                    }

                    $versionIds = array_map(function ($v) {
                        return $v->id;
                    }, $dependentProjects[$project_data->id]['versions']);

                    if (!in_array($version->id, $versionIds)) {
                        $dependentProjects[$project_data->id]['versions'][] = $version;
                    }
                }
            }

            $projectMembers = $project->active_users()->get();
            $project->delete();

            foreach ($projectMembers as $member) {
                $member->notify(new ProjectDeleted($project, Auth::user()));
            }

            foreach ($dependentProjects as $projectData) {
                $project_data = $projectData['project'];
                $owners = $project_data->owner;

                foreach ($owners as $owner) {
                    foreach ($projectData['versions'] as $version) {
                        $owner->notify(new BrokenDependencyNotification(
                            $project_data,
                            $version,
                            $project->id,
                            $project->name,
                            null,
                            Auth::user(),
                            true
                        ));
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete project: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
            ]);
            throw $e;
        }
    }
}
