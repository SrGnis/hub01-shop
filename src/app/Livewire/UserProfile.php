<?php

namespace App\Livewire;

use App\Enums\CollectionSystemType;
use App\Livewire\Concerns\InteractsWithProjectCollections;
use App\Models\Collection;
use App\Models\CollectionEntry;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

class UserProfile extends Component
{
    use Toast;
    use InteractsWithProjectCollections;

    public User $user;

    #[Url(as: 'tab')]
    public string $activeTab = 'projects';

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

        return $query
            ->withCount('entries')
            ->with([
                'entries.project:id,name,logo_path',
            ])
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

    #[Computed]
    public function aggregateFavorites()
    {
        return CollectionEntry::query()
            ->join('collection', 'collection.uid', '=', 'collection_entry.collection_uid')
            ->join('project', 'project.id', '=', 'collection_entry.project_id')
            ->join('membership', function ($join) {
                $join->on('membership.project_id', '=', 'project.id')
                    ->where('membership.user_id', '=', $this->user->id)
                    ->where('membership.status', '=', 'active');
            })
            ->whereNull('project.deleted_at')
            ->where('collection.system_type', CollectionSystemType::FAVORITES->value)
            ->count();
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
            ->with([
                'entries.project:id,name,logo_path',
            ])
            ->first();
    }
}
