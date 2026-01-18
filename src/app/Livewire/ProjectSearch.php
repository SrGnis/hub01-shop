<?php

namespace App\Livewire;

use App\Models\ProjectType;
use App\Services\ProjectService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ProjectSearch extends Component
{
    use WithPagination;
    use Toast;

    public ProjectType $projectType;

    // Search and filter properties
    #[Session]
    public string $search = '';
    #[Session]
    public array $selectedTags = [];
    #[Session]
    public array $selectedVersionTags = [];
    #[Session]
    public string $orderBy = 'downloads';
    #[Session]
    public string $orderDirection = 'desc';
    #[Session]
    public int $resultsPerPage = 10;
    private ProjectService $projectService;

    public function boot(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function mount(ProjectType $projectType)
    {
        $this->projectType = $projectType;
    }

    public function render()
    {
        return view('livewire.project-search');
    }

    #[Computed]
    public function projects(): LengthAwarePaginator
    {
        return $this->projectService->searchProjects(
            projectType: $this->projectType,
            search: $this->search,
            selectedTags: $this->selectedTags,
            selectedVersionTags: $this->selectedVersionTags,
            orderBy: $this->orderBy,
            orderDirection: $this->orderDirection,
            resultsPerPage: $this->resultsPerPage
        );
    }

    #[Computed]
    public function tagGroups(): Collection
    {
        return $this->projectService->getTagGroups($this->projectType);
    }

    #[Computed]
    public function versionTagGroups(): Collection
    {
        return $this->projectService->getVersionTagGroups($this->projectType);
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

    #[Computed]
    public function perPageOptions(): array
    {
        return $this->projectService->getPerPageOptions();
    }

    // Event handlers for resetting pagination
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedTags()
    {
        $this->resetPage();
    }

    public function updatedSelectedVersionTags()
    {
        $this->resetPage();
    }

    public function updatedOrderBy()
    {
        $this->resetPage();
    }

    public function updatedOrderDirection()
    {
        $this->resetPage();
    }

    public function updatedResultsPerPage()
    {
        $this->resetPage();
    }



    public function clearFilters()
    {
        $this->selectedTags = [];
        $this->selectedVersionTags = [];
        $this->search = '';
        $this->resetPage();
    }
}
