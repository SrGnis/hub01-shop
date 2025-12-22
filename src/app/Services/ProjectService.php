<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
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
use App\Notifications\ProjectApprovalRequested;
use App\Notifications\ProjectApproved;
use App\Notifications\ProjectDeleted;
use App\Notifications\ProjectRejected;
use App\Notifications\ProjectRestored;
use App\Notifications\ProjectSubmittedForReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ProjectService
{
    protected ProjectQuotaService $quotaService;

    public function __construct(ProjectQuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }
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
            ->where('project_type_id', $projectType->id)
            ->whereNull('deactivated_at');

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
     * @param  User|null  $user  The user to associate with the new project, not used when updating an existing project
     * @param  array  $data  The project data
     * @param  string|null  $logoPath  The new logo path, empty string to remove, null to keep existing
     */
    public function saveProject(
        ?Project $project,
        ?User $user,
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

        // If no user provided, raise error
        if (! $user) {
            throw new \Exception('User not provided.');
        }

        // Validate quota before creating project
        $this->quotaService->validateProjectCreation($user);

        // Set project to pending approval status by default
        $projectData = array_merge($data, [
            'logo_path' => $logoPath,
            'approval_status' => ApprovalStatus::PENDING,
            'submitted_at' => now(),
        ]);

        $project = Project::create($projectData);
        $project->tags()->attach($data['selectedTags'] ?? []);

        // Create owner membership
        $membership = new Membership([
            'role' => 'owner',
            'primary' => true,
        ]);
        $membership->user()->associate($user);
        $membership->project()->associate($project);
        $membership->save();

        // Notify user that project was submitted
        $user->notify(new ProjectSubmittedForReview($project));

        // Notify all admins about the new project pending approval
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new ProjectApprovalRequested($project, $user));
        }

        return $project;
    }

    /**
     * Add a member to a project
     *
     * @param  Project  $project  The project to add the member to
     * @param  string  $userName  The username of the new member
     * @param  string  $role  The role of the new member
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
        $inviter = Auth::user(); // User who is inviting the new member, null if is a System invitation
        if ($inviter) {
            $membership->inviter()->associate($inviter);
        }
        $membership->save();

        $user->notify(new MembershipInvitation($membership));
    }

    /**
     * Remove a member from a project
     *
     * @param  Project  $project  The project to remove the member from
     * @param  int  $membershipId  The ID of the membership to remove
     */
    public function removeMember(Project $project, int $membershipId): bool
    {
        $membership = Membership::findOrFail($membershipId);
        $currentUser = Auth::user(); // User who is removing the member, null if is a System removal
        $isSelfRemoval = $membership->user_id === Auth::id();

        if ($membership->project_id !== $project->id) {
            throw new \Exception('Invalid membership.');
        }

        if ($isSelfRemoval && $membership->primary) {
            throw new \Exception('You cannot remove yourself as the primary owner. Transfer ownership to another member first.');
        }

        if ($membership->primary && $project->memberships()->where('primary', true)->count() <= 1) {
            throw new \Exception('You cannot remove the last primary member of the project.');
        }

        $removedUser = User::find($membership->user_id);

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
     * @param  Project  $project  The project to set the primary member for
     * @param  int  $membershipId  The ID of the membership to set as primary
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

        DB::beginTransaction();

        try {
            $project->memberships()
                ->where('id', '!=', $membership->id)
                ->where('primary', true)
                ->update(['primary' => false]);

            $membership->update(['primary' => true]);

            $newPrimaryUser = User::find($membership->user_id);
            $currentUser = Auth::user(); // User who is setting the primary member, null if is a System removal
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
     * Delete a project and notify affected parties.
     *
     * This method performs the following actions within a database transaction:
     * 1. Identifies all project versions that depend on this project (or its versions)
     * 2. Soft-deletes the project
     * 3. Notifies all active project members about the deletion
     * 4. Notifies owners of dependent projects about broken dependencies
     *
     * @param  Project  $project  The project to delete
     *
     * @throws \Exception If the deletion fails (transaction is rolled back)
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

    public function restoreProject(Project $project): void
    {
        DB::beginTransaction();

        try {
            $project->restore();

            $projectMembers = $project->active_users()->get();

            foreach ($projectMembers as $member) {
                $member->notify(new ProjectRestored($project, Auth::user()));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to restore project: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
            ]);
            throw $e;
        }
    }

    /**
     * Submit a project for review (resubmission after rejection)
     */
    public function submitProjectForReview(Project $project): void
    {
        $project->submit();

        // Get project owner
        $owner = $project->owner->first();
        if ($owner) {
            $owner->notify(new ProjectSubmittedForReview($project));
        }

        // Notify admins about resubmission
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new ProjectApprovalRequested($project, $owner));
        }
    }

    /**
     * Approve a project
     */
    public function approveProject(Project $project, User $admin): void
    {
        DB::beginTransaction();

        try {
            $project->approve($admin);

            // Notify project owner
            $owner = $project->owner->first();
            if ($owner) {
                $owner->notify(new ProjectApproved($project, $admin));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve project: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'admin_id' => $admin->id,
            ]);
            throw $e;
        }
    }

    /**
     * Reject a project with a reason
     */
    public function rejectProject(Project $project, User $admin, string $reason): void
    {
        DB::beginTransaction();

        try {
            $project->reject($admin, $reason);

            // Notify project owner
            $owner = $project->owner->first();
            if ($owner) {
                $owner->notify(new ProjectRejected($project, $admin, $reason));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject project: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'admin_id' => $admin->id,
            ]);
            throw $e;
        }
    }
}
