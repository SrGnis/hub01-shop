<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

class UserProfile extends Component
{
    use Toast;

    protected ProjectService $projectService;

    public User $user;

    public function boot(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function mount(User $user)
    {
        $this->user = $user;
    }

    #[Computed]
    public function activeProjects()
    {
        // only show deactivated projects to the authenticated owner

        return $this->user->projects()
            ->whereNull('project.deleted_at')
            ->where('membership.status', 'active')
            ->where(function ($query) {
                $query->whereNull('project.deactivated_at')
                    ->orWhere('user_id', Auth::id());
            })
            ->with(['projectType', 'tags.tagGroup', 'owner'])
            ->orderBy('project.created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function deletedProjects()
    {
        // Only show deleted projects to the authenticated owner
        if (!Auth::check() || Auth::id() !== $this->user->id) {
            return collect();
        }

        return $this->user->projects()
            ->onlyTrashed()
            ->where('membership.status', 'active')
            ->with(['projectType', 'tags.tagGroup', 'owner'])
            ->orderBy('project.deleted_at', 'desc')
            ->get();
    }

    #[Computed]
    public function ownedProjectsCount()
    {
        return $this->user->ownedProjects()
            ->withoutGlobalScopes()
            ->whereNull('project.deleted_at')
            ->count();
    }

    #[Computed]
    public function contributionsCount()
    {
        return $this->user->projects()
            ->withoutGlobalScopes()
            ->whereNull('project.deleted_at')
            ->where('membership.status', 'active')
            ->wherePivot('primary', false)
            ->count();
    }

    public function restoreProject($projectId)
    {
        $project = Project::withoutGlobalScopes()->onlyTrashed()->findOrFail($projectId);

        // Authorization: Only the primary owner can restore
        $isPrimaryOwner = $project->memberships()
            ->where('user_id', Auth::id())
            ->where('primary', true)
            ->exists();

        if (!$isPrimaryOwner) {
            $this->error('You are not authorized to restore this project.');

            return;
        }

        try {
            $this->projectService->restoreProject($project);
            $this->success('Project restored successfully!');

            // Refresh the computed properties
            unset($this->activeProjects);
            unset($this->deletedProjects);
        } catch (\Exception) {
            $this->error('Failed to restore project. Please try again.');
        }
    }

    public function render()
    {
        /** @disregard P1013 */
        return view('livewire.user-profile')
            ->title($this->user->name);
    }
}

