<?php

namespace App\Livewire\Admin;

use App\Models\Project;
use App\Models\ProjectType;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectManagement extends Component
{
    use WithPagination;

    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    public $filterType = '';

    public $filterStatus = '';

    // Confirmation
    public $confirmingProjectDeletion = false;

    public $projectToDelete = null;

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

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
        $project = Project::find($this->projectToDelete);

        if ($project) {
            $project->delete();
            session()->flash('message', 'Project deleted successfully.');
        }

        $this->confirmingProjectDeletion = false;
        $this->projectToDelete = null;
    }

    public function restoreProject($projectId)
    {
        $project = Project::withTrashed()->find($projectId);

        if ($project && $project->trashed()) {
            $project->restore();
            session()->flash('message', 'Project restored successfully.');
        }
    }

    public function render()
    {
        $projectsQuery = Project::withTrashed()
            ->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('slug', 'like', '%'.$this->search.'%');
            });

        if ($this->filterType) {
            $projectsQuery->whereHas('projectType', function ($query) {
                $query->where('value', $this->filterType);
            });
        }

        if ($this->filterStatus === 'active') {
            $projectsQuery->whereNull('deleted_at')->where('status', 'active');
        } elseif ($this->filterStatus === 'inactive') {
            $projectsQuery->whereNull('deleted_at')->where('status', 'inactive');
        } elseif ($this->filterStatus === 'deleted') {
            $projectsQuery->whereNotNull('deleted_at');
        }

        $projects = $projectsQuery->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

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

    public function layout()
    {
        return 'components.layouts.admin';
    }
}
