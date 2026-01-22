<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\Project;
use App\Models\Scopes\ProjectFullScope;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Services\ProjectService;
use App\Models\ProjectVersionTag;
use Illuminate\Database\Eloquent\Builder;

class ProjectShow extends Component
{
    use WithPagination;
    use Toast;

    #[Locked]
    public string $projectSlug;

    #[Url(as: 'tab')]
    public string $activeTab = 'description';

    public int $versionsPerPage = 10;
    public int $changelogPerPage = 10;

    public array $sortBy = ['column' => 'release_date', 'direction' => 'desc'];

    public array $selectedVersionTags = [];

    // Date range filter properties
    public string $releaseDatePeriod = 'all';
    public ?string $releaseDateStart = null;
    public ?string $releaseDateEnd = null;

    protected ProjectService $projectService;

    public function boot(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function mount($project, ?string $activeTab = null)
    {
        $this->projectSlug = $project;

        // Check if the project is deactivated
        if ($this->project->isDeactivated()) {

            // use normal laravel flash message toast is not working here
            session()->flash('error', 'This project has been deactivated and cannot be viewed.');
            return redirect()->route('project-search', ['projectType' => $this->project->projectType]);
        }

    }

    public function updatedVersionsPerPage()
    {
        $this->resetPage('versionsPage');
    }

    public function updatedChangelogPerPage()
    {
        $this->resetPage('changelogPage');
    }

    public function updatedSelectedVersionTags()
    {
        $this->resetPage('versionsPage');
    }

    public function updatedReleaseDatePeriod(){
        $this->resetPage('versionsPage');
    }

    public function updatedReleaseDateStart(){
        $this->resetPage('versionsPage');
    }

    public function updatedReleaseDateEnd(){
        $this->resetPage('versionsPage');
    }

    #[Computed]
    public function project()
    {
        /** @disregard P1006, P1005 */
        return Project::accessScope()->where('slug', $this->projectSlug)
            ->firstOrFail();
    }

    #[Computed]
    public function versions()
    {
        return $this->project->versions()
            ->with([
                'tags.tagGroup',
                'project.projectType',
                'project.owner'
            ])
            ->when(!empty($this->selectedVersionTags) || $this->releaseDatePeriod !== 'all', function (Builder $query) {
                // Filter by version tags
                if (!empty($this->selectedVersionTags)) {
                    $query->whereHas('tags', function (Builder $q) {
                        $q->whereIn('project_version_tag.id', $this->selectedVersionTags);
                    }, '>=', count($this->selectedVersionTags));
                }

                // Filter by release date
                if ($this->releaseDatePeriod !== 'all') {
                    $startDate = match ($this->releaseDatePeriod) {
                        'last_30_days' => now()->subDays(30),
                        'last_90_days' => now()->subDays(90),
                        'last_year' => now()->subYear(),
                        'custom' => $this->releaseDateStart ? \Carbon\Carbon::parse($this->releaseDateStart)->startOfDay() : null,
                        default => null,
                    };

                    $endDate = match ($this->releaseDatePeriod) {
                        'custom' => $this->releaseDateEnd ? \Carbon\Carbon::parse($this->releaseDateEnd)->endOfDay() : null,
                        default => null,
                    };

                    if ($startDate) {
                        $query->where('release_date', '>=', $startDate);
                    }

                    if ($endDate) {
                        $query->where('release_date', '<=', $endDate);
                    }
                }
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->versionsPerPage, ['*'], 'versionsPage');
    }

    #[Computed]
    public function changelogVersions()
    {
        return $this->project->versions()
            ->whereNotNull('changelog')
            ->orderBy('release_date', 'desc')
            ->paginate($this->changelogPerPage, ['*'], 'changelogPage');
    }

    #[Computed]
    public function versionTagGroups()
    {
        return $this->projectService->getVersionTagGroups($this->project->projectType);
    }

    public function render()
    {
        return view('livewire.project-show', [
            'project' => $this->project,
            'versions' => $this->versions,
            'changelogVersions' => $this->changelogVersions,
        ]);
    }
}

