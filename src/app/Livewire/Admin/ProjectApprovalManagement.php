<?php

namespace App\Livewire\Admin;

use App\Models\Project;
use App\Models\ProjectType;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('components.layouts.admin')]
class ProjectApprovalManagement extends Component
{
    use WithPagination, Toast;

    public $search = '';

    public $sortBy = ['column' => 'submitted_at', 'direction' => 'desc'];

    public $perPage = 10;

    public $filterType = '';

    public $filterStatus = 'pending'; // Default to pending

    // Rejection modal
    public $confirmingProjectRejection = false;

    public $projectToReject = null;

    public $rejectionReason = '';

    private ProjectService $projectService;

    public function boot(ProjectService $projectService)
    {
        $this->projectService = $projectService;
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

    public function confirmProjectRejection($projectId)
    {
        $this->confirmingProjectRejection = true;
        $this->projectToReject = $projectId;
        $this->rejectionReason = '';
    }

    public function approveProject($projectId)
    {
        $project = Project::withTrashed()->find($projectId);

        if (!$project || !$project->isPending()) {
            $this->error('Project not found or not pending approval.');
            return;
        }

        try {
            $this->projectService->approveProject($project, Auth::user());
            $this->success('Project "' . $project->name . '" approved successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to approve project: ' . $e->getMessage());
        }
    }

    public function rejectProject()
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:10|max:1000',
        ]);

        $project = Project::withTrashed()->find($this->projectToReject);

        if (!$project || !$project->isPending()) {
            $this->error('Project not found or not pending approval.');
            $this->confirmingProjectRejection = false;
            return;
        }

        try {
            $this->projectService->rejectProject($project, Auth::user(), $this->rejectionReason);
            $this->success('Project "' . $project->name . '" rejected successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to reject project: ' . $e->getMessage());
        }

        $this->confirmingProjectRejection = false;
        $this->projectToReject = null;
        $this->rejectionReason = '';
    }

    public function render()
    {
        // Handle sorting
        $sortColumn = $this->sortBy['column'];
        $sortDirection = $this->sortBy['direction'];

        $projectsQuery = Project::withTrashed();

        // Apply search
        $projectsQuery->where(function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('slug', 'like', '%' . $this->search . '%');
        });

        // Filter by project type
        if ($this->filterType) {
            $projectsQuery->whereHas('projectType', function ($query) {
                $query->where('value', $this->filterType);
            });
        }

        // Filter by approval status
        if ($this->filterStatus === 'pending') {
            $projectsQuery->pending();
        } elseif ($this->filterStatus === 'approved') {
            $projectsQuery->approved();
        } elseif ($this->filterStatus === 'rejected') {
            $projectsQuery->rejected();
        }

        // Apply sorting
        if ($sortColumn === 'owner') {
            // Join with memberships and users to sort by owner name
            $projectsQuery->join('membership', 'project.id', '=', 'membership.project_id')
                ->join('users', 'membership.user_id', '=', 'users.id')
                ->where('membership.primary', true)
                ->select('project.*')
                ->orderBy('users.name', $sortDirection);
        } elseif ($sortColumn === 'type') {
            // Join with project_type to sort by type
            $projectsQuery->join('project_type', 'project.project_type_id', '=', 'project_type.id')
                ->select('project.*')
                ->orderBy('project_type.display_name', $sortDirection);
        } else {
            $projectsQuery->orderBy($sortColumn, $sortDirection);
        }

        $projects = $projectsQuery->paginate($this->perPage);

        // Load relationships
        $projects->each(function ($project) {
            $project->load(['owner', 'projectType']);
            $project->append('formatted_size');
        });

        $projectTypes = ProjectType::all();

        return view('livewire.admin.project-approval-management', [
            'projects' => $projects,
            'projectTypes' => $projectTypes,
        ]);
    }
}
