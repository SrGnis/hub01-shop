<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Scopes\ProjectFullScope;
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

    // TODO: move it for reusing it in API
    #[Computed]
    public function activeProjects()
    {
        $query = $this->user->projects();

        $query->accessScope();
        $query->orderBy('project.created_at', 'desc');

        return $query->get();
    }

    // TODO: move it for reusing it in API
    #[Computed]
    public function deletedProjects()
    {
        // Only show deleted projects to the authenticated owner
        if (!Auth::check() || Auth::id() !== $this->user->id) {
            return collect();
        }

        return $this->user->projects()
            ->onlyTrashed()
            ->accessScope()
            ->orderBy('project.deleted_at', 'desc')
            ->get();
    }

    // TODO: move it for reusing it in API
    #[Computed]
    public function ownedProjectsCount()
    {
        return $this->user->ownedProjects()
            ->withoutGlobalScopes()
            ->whereNull('project.deleted_at')
            ->count();
    }

    // TODO: move it for reusing it in API
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

    // TODO: move it to service
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

