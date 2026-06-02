<?php

namespace App\Livewire;

use App\Enums\CollectionSystemType;
use App\Livewire\Concerns\InteractsWithProjectCollections;
use App\Models\Collection;
use App\Models\CollectionEntry;
use App\Models\Project;
use App\Models\ProjectTagGroup;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionTagGroup;
use App\Models\User;
use App\Services\CollectionService;
use App\Services\ProjectService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class UserProfile extends Component
{
    use Toast;
    use InteractsWithProjectCollections;
    use WithPagination;

    public User $user;

    #[Url(as: 'tab')]
    public string $activeTab = 'projects';

    public string $projectSearch = '';

    public array $selectedTags = [];

    public array $selectedVersionTags = [];

    public string $orderBy = 'created_at';

    public string $orderDirection = 'desc';

    public string $releaseDatePeriod = 'all';

    public ?string $releaseDateStart = null;

    public ?string $releaseDateEnd = null;

    public string $collectionSearch = '';

    public string $collectionVisibility = 'all';

    public int $collectionPerPage = 10;

    private ProjectService $projectService;

    private CollectionService $collectionService;

    public function boot(ProjectService $projectService, CollectionService $collectionService): void
    {
        $this->projectService = $projectService;
        $this->collectionService = $collectionService;
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
        $this->applyProjectFilters($query);
        $this->applyProjectOrdering($query);

        return $query->get();
    }

    #[Computed]
    public function tagGroups()
    {
        return ProjectTagGroup::query()
            ->with(['tags.projectTypes'])
            ->whereHas('tags')
            ->get();
    }

    #[Computed]
    public function versionTagGroups()
    {
        return ProjectVersionTagGroup::query()
            ->with(['tags.projectTypes'])
            ->whereHas('tags')
            ->get();
    }

    #[Computed]
    public function orderOptions(): array
    {
        return $this->projectService->getOrderOptions();
    }

    #[Computed]
    public function directionOptions(): array
    {
        return $this->projectService->getDirectionOptions();
    }

    public function clearProjectFilters(): void
    {
        $this->projectSearch = '';
        $this->selectedTags = [];
        $this->selectedVersionTags = [];
        $this->releaseDatePeriod = 'all';
        $this->releaseDateStart = null;
        $this->releaseDateEnd = null;
    }

    #[Computed]
    public function visibleCollections(): LengthAwarePaginator
    {
        $isOwner = Auth::check() && Auth::id() === $this->user->id;

        return $this->collectionService->paginateForOwner(
            user: $this->user,
            search: $this->collectionSearch ?: null,
            visibility: $isOwner ? $this->collectionVisibility : 'public',
            orderBy: 'updated_at',
            orderDirection: 'desc',
            perPage: $this->collectionPerPage,
            excludeSystem: true,
            withEntriesCount: true,
            withEntriesProject: true,
        );
    }

    #[Computed]
    public function collectionVisibilityOptions(): array
    {
        if (!Auth::check() || Auth::id() !== $this->user->id) {
            return [['id' => 'all', 'name' => 'All visibility']];
        }

        return [
            ['id' => 'all', 'name' => 'All visibility'],
            ['id' => 'public', 'name' => 'Public'],
            ['id' => 'private', 'name' => 'Private'],
            ['id' => 'hidden', 'name' => 'Hidden'],
        ];
    }

    public function updatedCollectionSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCollectionVisibility(): void
    {
        $this->resetPage();
    }

    public function updatedCollectionPerPage(): void
    {
        $this->resetPage();
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

        return $this->collectionService->getFavoritesForUser($this->user);
    }

    private function applyProjectFilters($query): void
    {
        if ($this->projectSearch !== '') {
            $query->where(function (Builder $builder) {
                $builder->where('project.name', 'like', '%' . $this->projectSearch . '%')
                    ->orWhere('project.summary', 'like', '%' . $this->projectSearch . '%');
            });
        }

        if (count($this->selectedTags)) {
            $query->whereHas('tags', function (Builder $tagQuery) {
                $tagQuery->withoutGlobalScope('display_priority_order');
                $tagQuery->whereIn('tag_id', $this->selectedTags);
            }, '>=', count($this->selectedTags));
        }

        if (count($this->selectedVersionTags) || $this->releaseDatePeriod !== 'all') {
            $query->whereHas('versions', function (Builder $versionQuery) {
                if (count($this->selectedVersionTags)) {
                    $versionQuery->whereHas('tags', function (Builder $tagQuery) {
                        $tagQuery->withoutGlobalScope('display_priority_order');
                        $tagQuery->whereIn('tag_id', $this->selectedVersionTags);
                    }, '>=', count($this->selectedVersionTags));
                }

                if ($this->releaseDatePeriod !== 'all') {
                    $startDate = match ($this->releaseDatePeriod) {
                        'last_30_days' => now()->subDays(30)->startOfDay(),
                        'last_90_days' => now()->subDays(90)->startOfDay(),
                        'last_year' => now()->subYear()->startOfDay(),
                        'custom' => $this->releaseDateStart ? \Carbon\Carbon::parse($this->releaseDateStart)->startOfDay() : null,
                        default => null,
                    };

                    $endDate = $this->releaseDatePeriod === 'custom' && $this->releaseDateEnd
                        ? \Carbon\Carbon::parse($this->releaseDateEnd)->endOfDay()
                        : null;

                    if ($startDate) {
                        $versionQuery->where('release_date', '>=', $startDate);
                    }

                    if ($endDate) {
                        $versionQuery->where('release_date', '<=', $endDate);
                    }
                }
            });
        }
    }

    private function applyProjectOrdering($query): void
    {
        $direction = $this->orderDirection === 'asc' ? 'asc' : 'desc';

        match ($this->orderBy) {
            'name' => $query->orderBy('project.name', $direction)->orderBy('project.id'),
            'updated_at' => $query->orderBy('last_update_time', $direction)->orderBy('project.id'),
            'favorites' => $query->orderBy('favorite_count', $direction)->orderBy('project.id'),
            'downloads' => $query->orderBy('downloads', $direction)->orderBy('project.id'),
            default => $query->orderBy('project.created_at', $direction)->orderBy('project.id'),
        };
    }
}
