<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectRestored;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class UserProfile extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Restore a soft-deleted project
     *
     * @param int $projectId
     * @return void
     */
    public function restoreProject($projectId)
    {
        $project = Project::onlyTrashed()->findOrFail($projectId);

        if (!Auth::check() || !Gate::allows('restore', $project)) {
            session()->flash('error', 'You do not have permission to restore this project.');
            return;
        }

        $projectMembers = $project->active_users()->get();

        DB::beginTransaction();

        try {
            $project->restore();

            foreach ($projectMembers as $member) {
                $member->notify(new ProjectRestored($project, Auth::user()));
            }

            DB::commit();

            session()->flash('message', 'Project restored successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to restore project: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => Auth::id()
            ]);

            session()->flash('error', 'Failed to restore project: ' . $e->getMessage());
        }
    }

    public function render(): View
    {
        $this->user->load(['projects' => function ($query) {
            if (Auth::check() && Auth::user()->id === $this->user->id) {
                $query->withTrashed();
            }
            $query->orderByPivot('primary', 'desc');
        }]);

        $activeProjects = $this->user->projects->where('deleted_at', null);

        $deletedProjects = $this->user->projects->where('deleted_at', '!=', null);

        return view('livewire.user-profile', [
            'activeProjects' => $activeProjects,
            'deletedProjects' => $deletedProjects
        ]);
    }
}
