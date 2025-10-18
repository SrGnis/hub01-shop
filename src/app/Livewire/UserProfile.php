<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

class UserProfile extends Component
{
    public User $user;

    public function mount(User $user)
    {
        $this->user = $user;
    }

    #[Computed]
    public function activeProjects()
    {
        return $this->user->projects()
            ->whereNull('project.deleted_at')
            ->where('membership.status', 'active')
            ->with(['projectType', 'tags.tagGroup', 'owner'])
            ->orderBy('project.created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function deletedProjects()
    {
        // Only show deleted projects to the authenticated owner
        if (!auth()->check() || auth()->id() !== $this->user->id) {
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
            ->where('user_id', auth()->id())
            ->where('primary', true)
            ->exists();

        if (!$isPrimaryOwner) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You are not authorized to restore this project.',
            ]);

            return;
        }

        DB::beginTransaction();

        try {
            $project->restore();

            DB::commit();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Project restored successfully!',
            ]);

            // Refresh the computed properties
            unset($this->activeProjects);
            unset($this->deletedProjects);
        } catch (\Exception) {
            DB::rollBack();

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to restore project. Please try again.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.user-profile')
            ->title($this->user->name);
    }
}

