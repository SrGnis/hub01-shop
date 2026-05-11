<?php

namespace App\Livewire\Platform;

use App\Services\ProjectService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Project;
use App\Models\ProjectType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;

class Projects extends Component
{
    use WithPagination;

    #[Session(key: 'platform-projects-search')]
    public string $search = '';

    #[Session(key: 'platform-projects-type')]
    public string $type = 'all';

    #[Session(key: 'platform-projects-status')]
    public string $status = 'all';

    /**
     * @var array{column: string, direction: 'asc'|'desc'}
     */
    #[Session(key: 'platform-projects-sort-by')]
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    #[Session(key: 'platform-projects-per-page')]
    public int $perPage = 10;

    private ProjectService $projectService;

    public function boot(ProjectService $projectService): void
    {
        $this->projectService = $projectService;
    }

    #[Computed]
    public function projects(): LengthAwarePaginator
    {
        return $this->projectService->projectsForUser(
            user: Auth::user(),
            search: trim($this->search),
            type: $this->type,
            status: $this->status,
            sortBy: $this->sortBy,
            perPage: $this->perPage,
        );
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function typeOptions(): array
    {
        $options = [['id' => 'all', 'name' => 'All types']];

        $types = ProjectType::query()
            ->orderBy('display_name')
            ->get(['value', 'display_name']);

        foreach ($types as $type) {
            $options[] = [
                'id' => (string) $type->value,
                'name' => (string) $type->display_name,
            ];
        }

        return $options;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.platform.projects');
    }
}
