<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithProjectCollections;
use App\Models\Collection;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;
use App\Services\CollectionService;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

class UserProfile extends Component
{
    use Toast;
    use InteractsWithProjectCollections;

    protected ProjectService $projectService;

    public User $user;

    #[Url(as: 'tab')]
    public string $activeTab = 'projects';

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

    #[Computed]
    public function visibleCollections()
    {
        $query = Collection::query()
            ->where('user_id', $this->user->id)
            ->whereNull('system_type')
            ->orderBy('updated_at', 'desc')
            ->orderBy('uid');

        if (!Auth::check() || Auth::id() !== $this->user->id) {
            $query->where('visibility', 'public');
        }

        return $query->with(['entries.project'])->get();
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

    #[Computed]
    public function aggregateDownloads()
    {
        $projectIds = Project::withoutGlobalScopes()
            ->whereNull('project.deleted_at')
            ->whereHas('memberships', function ($query) {
                $query->where('membership.user_id', $this->user->id)
                    ->where('membership.status', 'active');
            })
            ->pluck('project.id');

        return ProjectVersion::query()
            ->join('project_version_daily_download', 'project_version.id', '=', 'project_version_daily_download.project_version_id')
            ->whereIn('project_version.project_id', $projectIds)
            ->sum('project_version_daily_download.downloads');
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

            Log::info('Project restored by user', [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
            ]);

            $this->success('Project restored successfully!');

            // Refresh the computed properties
            unset($this->activeProjects);
            unset($this->deletedProjects);
        } catch (\Exception $e) {
            Log::error('Failed to restore project', [
                'project_id' => $projectId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->error('Failed to restore project. Please try again.');
        }
    }

    public function render()
    {
        /** @disregard P1013 */
        return view('livewire.user-profile')
            ->title($this->user->name);
    }

    #[Computed]
    public function favoritesCollection(): ?Collection
    {
        if (!Auth::check() || Auth::id() !== $this->user->id) {
            return null;
        }

        return Collection::query()
            ->where('user_id', $this->user->id)
            ->where('system_type', 'favorites')
            ->withCount('entries')
            ->first();
    }

    public function deleteCollection(string $uid): void
    {
        $collection = Collection::query()
            ->where('uid', $uid)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        try {
            app(CollectionService::class)->deleteCollection($collection);
            $this->success('Collection deleted successfully.');
            unset($this->visibleCollections);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }
}
