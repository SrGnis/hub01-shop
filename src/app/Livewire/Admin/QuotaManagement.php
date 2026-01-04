<?php

namespace App\Livewire\Admin;

use App\Models\Project;
use App\Models\ProjectType;
use App\Models\ProjectTypeQuota;
use App\Models\ProjectQuota;
use App\Models\User;
use App\Models\UserQuota;
use App\Services\ProjectQuotaService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('components.layouts.admin')]
class QuotaManagement extends Component
{
    use WithPagination, Toast;

    #[Url]
    public string $activeTab = 'project-types';

    // Search properties
    public string $searchProjectTypes = '';
    public string $searchProjects = '';
    public string $searchUsers = '';

    // Filter properties
    public string $filterProjectType = '';
    public string $filterRole = '';

    // Sort properties
    public array $sortBy = ['column' => '', 'direction' => 'asc'];

    // Modal state
    public bool $showProjectTypeModal = false;
    public bool $showProjectModal = false;
    public bool $showUserModal = false;

    // Editing state
    public ?int $editingProjectTypeId = null;
    public ?int $editingProjectId = null;
    public ?int $editingUserId = null;

    // Form data for editing
    public array $quotaForm = [];

    private ProjectQuotaService $quotaService;

    public function boot(ProjectQuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    public function mount()
    {
        // Nothing to mount
    }

    // Reset pagination when search or filters change
    public function updatedSearchProjectTypes()
    {
        $this->resetPage();
    }

    public function updatedSearchProjects()
    {
        $this->resetPage();
    }

    public function updatedSearchUsers()
    {
        $this->resetPage();
    }

    public function updatedFilterProjectType()
    {
        $this->resetPage();
    }

    public function updatedFilterRole()
    {
        $this->resetPage();
    }

    // Reset filters when changing tabs
    public function updatedActiveTab()
    {
        $this->searchProjectTypes = '';
        $this->searchProjects = '';
        $this->searchUsers = '';
        $this->filterProjectType = '';
        $this->filterRole = '';
        $this->resetPage();
    }

    // Project Type Quota Management
    public function editProjectTypeQuota($projectTypeId)
    {
        $this->editingProjectTypeId = $projectTypeId;
        $projectType = ProjectType::with('quota')->find($projectTypeId);
        
        $this->quotaForm = [
            'project_storage_max' => $projectType->quota?->project_storage_max ? $projectType->quota->project_storage_max / 1048576 : null,
            'versions_per_day_max' => $projectType->quota?->versions_per_day_max ?? null,
            'version_size_max' => $projectType->quota?->version_size_max ? $projectType->quota->version_size_max / 1048576 : null,
            'files_per_version_max' => $projectType->quota?->files_per_version_max ?? null,
            'file_size_max' => $projectType->quota?->file_size_max ? $projectType->quota->file_size_max / 1048576 : null,
        ];

        $this->showProjectTypeModal = true;
    }

    public function saveProjectTypeQuota()
    {
        $this->validate([
            'quotaForm.project_storage_max' => 'nullable|integer|min:0',
            'quotaForm.versions_per_day_max' => 'nullable|integer|min:0',
            'quotaForm.version_size_max' => 'nullable|integer|min:0',
            'quotaForm.files_per_version_max' => 'nullable|integer|min:0',
            'quotaForm.file_size_max' => 'nullable|integer|min:0',
        ]);

        $projectType = ProjectType::find($this->editingProjectTypeId);
        
        // Convert MB to bytes for storage fields
        $quotaData = [];
        if (!is_null($this->quotaForm['project_storage_max'])) {
            $quotaData['project_storage_max'] = $this->quotaForm['project_storage_max'] * 1048576;
        }
        if (!is_null($this->quotaForm['version_size_max'])) {
            $quotaData['version_size_max'] = $this->quotaForm['version_size_max'] * 1048576;
        }
        if (!is_null($this->quotaForm['file_size_max'])) {
            $quotaData['file_size_max'] = $this->quotaForm['file_size_max'] * 1048576;
        }
        if (!is_null($this->quotaForm['versions_per_day_max'])) {
            $quotaData['versions_per_day_max'] = $this->quotaForm['versions_per_day_max'];
        }
        if (!is_null($this->quotaForm['files_per_version_max'])) {
            $quotaData['files_per_version_max'] = $this->quotaForm['files_per_version_max'];
        }

        ProjectTypeQuota::updateOrCreate(
            ['project_type_id' => $this->editingProjectTypeId],
            $quotaData
        );

        $this->success('Quota settings saved for ' . $projectType->display_name);
        $this->cancelEdit();
    }

    // Project Quota Management
    public function editProjectQuota($projectId)
    {
        $this->editingProjectId = $projectId;
        $project = Project::withoutGlobalScopes()->with('quota')->find($projectId);
        
        $this->quotaForm = [
            'project_storage_max' => $project->quota?->project_storage_max ? $project->quota->project_storage_max / 1048576 : null,
            'versions_per_day_max' => $project->quota?->versions_per_day_max ?? null,
            'version_size_max' => $project->quota?->version_size_max ? $project->quota->version_size_max / 1048576 : null,
            'files_per_version_max' => $project->quota?->files_per_version_max ?? null,
            'file_size_max' => $project->quota?->file_size_max ? $project->quota->file_size_max / 1048576 : null,
        ];

        $this->showProjectModal = true;
    }

    public function saveProjectQuota()
    {
        $this->validate([
            'quotaForm.project_storage_max' => 'nullable|integer|min:0',
            'quotaForm.versions_per_day_max' => 'nullable|integer|min:0',
            'quotaForm.version_size_max' => 'nullable|integer|min:0',
            'quotaForm.files_per_version_max' => 'nullable|integer|min:0',
            'quotaForm.file_size_max' => 'nullable|integer|min:0',
        ]);

        $project = Project::withoutGlobalScopes()->find($this->editingProjectId);
        
        // Convert MB to bytes for storage fields
        $quotaData = [];
        if (!is_null($this->quotaForm['project_storage_max'])) {
            $quotaData['project_storage_max'] = $this->quotaForm['project_storage_max'] * 1048576;
        }
        if (!is_null($this->quotaForm['version_size_max'])) {
            $quotaData['version_size_max'] = $this->quotaForm['version_size_max'] * 1048576;
        }
        if (!is_null($this->quotaForm['file_size_max'])) {
            $quotaData['file_size_max'] = $this->quotaForm['file_size_max'] * 1048576;
        }
        if (!is_null($this->quotaForm['versions_per_day_max'])) {
            $quotaData['versions_per_day_max'] = $this->quotaForm['versions_per_day_max'];
        }
        if (!is_null($this->quotaForm['files_per_version_max'])) {
            $quotaData['files_per_version_max'] = $this->quotaForm['files_per_version_max'];
        }

        ProjectQuota::updateOrCreate(
            ['project_id' => $this->editingProjectId],
            $quotaData
        );

        $this->success('Quota settings saved for ' . $project->name);
        $this->cancelEdit();
    }

    // User Quota Management
    public function editUserQuota($userId)
    {
        $this->editingUserId = $userId;
        $user = User::with('quota')->find($userId);
        
        $this->quotaForm = [
            'total_storage_max' => $user->quota?->total_storage_max ? $user->quota->total_storage_max / 1048576 : null,
        ];

        $this->showUserModal = true;
    }

    public function saveUserQuota()
    {
        $this->validate([
            'quotaForm.total_storage_max' => 'nullable|integer|min:0',
        ]);

        $user = User::find($this->editingUserId);
        
        $quotaData = [];
        if (!is_null($this->quotaForm['total_storage_max'])) {
            $quotaData['total_storage_max'] = $this->quotaForm['total_storage_max'] * 1048576;
        }

        UserQuota::updateOrCreate(
            ['user_id' => $this->editingUserId],
            $quotaData
        );

        $this->success('Quota settings saved for ' . $user->name);
        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->editingProjectTypeId = null;
        $this->editingProjectId = null;
        $this->editingUserId = null;
        $this->quotaForm = [];
        $this->showProjectTypeModal = false;
        $this->showProjectModal = false;
        $this->showUserModal = false;
    }

    public function render()
    {
        // Project Types with search and sorting
        $projectTypesQuery = ProjectType::with('quota');
        
        if ($this->searchProjectTypes) {
            $projectTypesQuery->where('display_name', 'like', '%' . $this->searchProjectTypes . '%');
        }

        if ($this->sortBy['column'] && $this->activeTab === 'project-types') {
            $projectTypesQuery->orderBy($this->sortBy['column'], $this->sortBy['direction']);
        }

        $projectTypes = $projectTypesQuery->paginate(10, ['*'], 'projectTypesPage');

        // Projects with search, filtering, and sorting
        $projectsQuery = Project::withoutGlobalScopes()
            ->with(['quota', 'projectType', 'owner']);

        if ($this->searchProjects) {
            $projectsQuery->where(function($query) {
                $query->where('name', 'like', '%' . $this->searchProjects . '%')
                      ->orWhere('slug', 'like', '%' . $this->searchProjects . '%');
            });
        }

        if ($this->filterProjectType) {
            $projectsQuery->where('project_type_id', $this->filterProjectType);
        }

        if ($this->sortBy['column'] && $this->activeTab === 'projects') {
            $sortCol = $this->sortBy['column'];
            $sortDir = $this->sortBy['direction'];
            
            if ($sortCol === 'type') {
                $projectsQuery->join('project_type', 'project.project_type_id', '=', 'project_type.id')
                    ->orderBy('project_type.display_name', $sortDir)
                    ->select('project.*');
            } elseif ($sortCol === 'owner') {
               // Sorting by owner is complex due to relationship, ignore for now to prevent crash
               // Ideally this would join memberships and users
            } else {
                $projectsQuery->orderBy($sortCol, $sortDir);
            }
        }

        $projects = $projectsQuery->paginate(10, ['*'], 'projectsPage')
            ->through(function ($project) {
                $project->storage_used = $this->quotaService->getProjectStorageUsed($project);
                return $project;
            });

        // Users with search, filtering, and sorting
        $usersQuery = User::with('quota');

        if ($this->searchUsers) {
            $usersQuery->where(function($query) {
                $query->where('name', 'like', '%' . $this->searchUsers . '%')
                      ->orWhere('email', 'like', '%' . $this->searchUsers . '%');
            });
        }

        if ($this->filterRole) {
            $usersQuery->where('role', $this->filterRole);
        }

        if ($this->sortBy['column'] && $this->activeTab === 'users') {
            $usersQuery->orderBy($this->sortBy['column'], $this->sortBy['direction']);
        }

        $users = $usersQuery->paginate(10, ['*'], 'usersPage')
            ->through(function ($user) {
                $user->storage_used = $this->quotaService->getTotalStorageUsed($user);
                return $user;
            });

        return view('livewire.admin.quota-management', [
            'projectTypes' => $projectTypes,
            'allProjectTypes' => ProjectType::orderBy('display_name')->get(),
            'projects' => $projects,
            'users' => $users,
        ]);
    }
}
