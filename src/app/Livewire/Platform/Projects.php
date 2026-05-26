<?php

namespace App\Livewire\Platform;

use App\Services\ProjectService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Project;
use App\Models\ProjectType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Projects extends Component
{
    use WithPagination;
    use Toast;

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
            includeDeleted: $this->status === 'all',
        );
    }

    public function restoreProject(int $projectId): void
    {
        $project = Project::withoutGlobalScopes()->onlyTrashed()->findOrFail($projectId);

        if (! Gate::allows('restore', $project)) {
            $this->error('You do not have permission to restore this project.');

            return;
        }

        try {
            $this->projectService->restoreProject($project);

            Log::info('Project restored from dashboard', [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
            ]);

            unset($this->projects);

            $this->success('Project restored successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to restore project from dashboard', [
                'project_id' => $projectId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            $this->error('Failed to restore project. Please try again.');
        }
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
