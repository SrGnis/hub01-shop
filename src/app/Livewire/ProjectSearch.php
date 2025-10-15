<?php

namespace App\Livewire;

use App\Models\ProjectType;
use App\Services\ProjectService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectSearch extends Component
{
    use WithPagination;

    public ProjectType $projectType;

    // Search and filter properties
    public string $search = '';
    public array $selectedTags = [];
    public array $selectedVersionTags = [];
    public string $orderBy = 'downloads';
    public string $orderDirection = 'desc';
    public int $resultsPerPage = 10;

    public function mount(ProjectType $projectType)
    {
        $this->projectType = $projectType;
    }

    private function getProjectService(): ProjectService
    {
        return app(ProjectService::class);
    }

    public function render()
    {
        return view('livewire.project-search');
    }

    #[Computed]
    public function projects(): LengthAwarePaginator
    {
        return $this->getProjectService()->searchProjects(
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
        return $this->getProjectService()->getTagGroups($this->projectType);
    }

    #[Computed]
    public function versionTagGroups(): Collection
    {
        return $this->getProjectService()->getVersionTagGroups($this->projectType);
    }

    #[Computed]
    public function orderOptions(): array
    {
        return $this->getProjectService()->getOrderOptions();
    }

    #[Computed]
    public function directionOptions(): array
    {
        return $this->getProjectService()->getDirectionOptions();
    }

    #[Computed]
    public function perPageOptions(): array
    {
        return $this->getProjectService()->getPerPageOptions();
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
