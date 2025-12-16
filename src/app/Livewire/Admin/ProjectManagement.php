<?php

namespace App\Livewire\Admin;

use App\Models\Project;
use App\Models\ProjectType;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.admin')]
class ProjectManagement extends Component
{
    use WithPagination, Toast;

    public $search = '';

    public $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public $perPage = 10;

    public $filterType = '';

    public $filterStatus = '';

    // Confirmation
    public $confirmingProjectDeletion = false;

    public $projectToDelete = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function confirmProjectDeletion($projectId)
    {
        $this->confirmingProjectDeletion = true;
        $this->projectToDelete = $projectId;
    }

    public function deleteProject()
    {
        $project = Project::withTrashed()->find($this->projectToDelete);

        if ($project) {
            $project->delete();
            $this->success('Project deleted successfully.');
        }

        $this->confirmingProjectDeletion = false;
        $this->projectToDelete = null;
    }

    public function restoreProject($projectId)
    {
        $project = Project::withTrashed()->find($projectId);

        if ($project && $project->trashed()) {
            $project->restore();
            $this->success('Project restored successfully.');
        }
    }

    public function render()
    {
        // Handle sorting
        $sortColumn = $this->sortBy['column'];
        $sortDirection = $this->sortBy['direction'];

        $projectsQuery = Project::withTrashed();

        // Apply search with qualified column names for size sorting
        if ($sortColumn === 'size') {
            $projectsQuery->where(function ($query) {
                $query->where('project.name', 'like', '%'.$this->search.'%')
                    ->orWhere('project.slug', 'like', '%'.$this->search.'%');
            });
        } else {
            $projectsQuery->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('slug', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterType) {
            $projectsQuery->whereHas('projectType', function ($query) {
                $query->where('value', $this->filterType);
            });
        }

        if ($this->filterStatus === 'active') {
            $projectsQuery->whereNull('project.deleted_at')->where('project.status', 'active');
        } elseif ($this->filterStatus === 'inactive') {
            $projectsQuery->whereNull('project.deleted_at')->where('project.status', 'inactive');
        } elseif ($this->filterStatus === 'deleted') {
            $projectsQuery->whereNotNull('project.deleted_at');
        }

        if ($sortColumn === 'type') {
            // Join with project_type table to sort by type
            $projectsQuery->join('project_type', 'project.project_type_id', '=', 'project_type.id')
                ->select('project.*')
                ->orderBy('project_type.display_name', $sortDirection);
        } elseif ($sortColumn === 'size') {
            // For size, we need to calculate it via subquery
            $projectsQuery->leftJoin('project_version', 'project.id', '=', 'project_version.project_id')
                ->leftJoin('project_file', 'project_version.id', '=', 'project_file.project_version_id')
                ->select(
                    'project.id',
                    'project.name',
                    'project.slug',
                    'project.summary',
                    'project.description',
                    'project.logo_path',
                    'project.website',
                    'project.issues',
                    'project.source',
                    'project.status',
                    'project.project_type_id',
                    'project.created_at',
                    'project.updated_at',
                    'project.deleted_at'
                )
                ->selectRaw('COALESCE(SUM(project_file.size), 0) as total_size')
                ->groupBy(
                    'project.id',
                    'project.name',
                    'project.slug',
                    'project.summary',
                    'project.description',
                    'project.logo_path',
                    'project.website',
                    'project.issues',
                    'project.source',
                    'project.status',
                    'project.project_type_id',
                    'project.created_at',
                    'project.updated_at',
                    'project.deleted_at'
                )
                ->orderBy('total_size', $sortDirection);
        } else {
            // Default sorting for other columns
            $projectsQuery->orderBy($sortColumn, $sortDirection);
        }

        $projects = $projectsQuery->paginate($this->perPage);

        // Append formatted_size attribute to each project
        $projects->each(function ($project) {
            $project->append('formatted_size');
        });

        $projectTypes = ProjectType::all();

        return view('livewire.admin.project-management', [
            'projects' => $projects,
            'projectTypes' => $projectTypes,
        ]);
    }
}
