<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectTagGroup;
use App\Models\ProjectType;
use App\Models\ProjectVersionTagGroup;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectSearch extends Component
{
    use WithPagination;

    public ProjectType $projectType;

    public $search = '';

    public $selectedTags = [];

    public $selectedVersionTags = [];

    public $orderBy = 'downloads';

    public $orderDirection = 'desc';

    public $resultsPerPage = 10;

    public $orderOptions = [
        ['id' => 'name', 'name' => 'Project Name', 'icon' => 'lucide-text'],
        ['id' => 'created_at', 'name' => 'Creation Date', 'icon' => 'lucide-calendar'],
        ['id' => 'latest_version', 'name' => 'Update Date', 'icon' => 'lucide-refresh-cw'],
        ['id' => 'downloads', 'name' => 'Downloads', 'icon' => 'lucide-download'],
    ];

    public $directionOptions = [
        ['id' => 'asc', 'name' => 'Ascending', 'icon' => 'lucide-arrow-up'],
        ['id' => 'desc', 'name' => 'Descending', 'icon' => 'lucide-arrow-down'],
    ];

    public $perPageOptions = [
        ['id' => 10, 'name' => '10'],
        ['id' => 25, 'name' => '25'],
        ['id' => 50, 'name' => '50'],
        ['id' => 100, 'name' => '100'],
    ];

    public function mount(ProjectType $projectType)
    {
        $this->projectType = $projectType;
    }

    public function render()
    {
        return view('livewire.project-search');
    }

    #[Computed]
    public function projects()
    {
        $projects = Project::where('name', 'like', '%'.$this->search.'%')
            ->where('project_type_id', $this->projectType->id)
            ->withSum('versions', 'downloads')
            ->withMax('versions', 'release_date')
            ->with('owner', 'tags', 'projectType');

        // Filter by project tags
        if (count($this->selectedTags)) {
            $projects->whereHas('tags', function ($query) {
                $query->whereIn('tag_id', $this->selectedTags);
            }, '>=', count($this->selectedTags));
        }

        // Filter by version tags
        if (count($this->selectedVersionTags)) {
            $projects->whereHas('versions.tags', function ($query) {
                $query->whereIn('tag_id', $this->selectedVersionTags);
            });
        }

        // Apply ordering based on selected option
        switch ($this->orderBy) {
            case 'name':
                $projects->orderBy('name', $this->orderDirection);
                break;
            case 'created_at':
                $projects->orderBy('created_at', $this->orderDirection);
                break;
            case 'latest_version':
                $projects->orderBy('versions_max_release_date', $this->orderDirection);
                break;
            case 'downloads':
            default:
                $projects->orderBy('versions_sum_downloads', $this->orderDirection);
                break;
        }

        return $projects->paginate($this->resultsPerPage);
    }

    #[Computed]
    public function tagGroups()
    {
        // TODO: refactor this
        $cacheKey = 'project_tag_groups_by_type_'.$this->projectType->value;

        return Cache::remember($cacheKey, now()->addHours(24), function () {
            return ProjectTagGroup::whereHas('projectTypes', function ($query) {
                $query->where('project_type_id', $this->projectType->id);
            })->with(['tags' => function ($query) {
                $query->whereHas('projectTypes', function ($subQuery) {
                    $subQuery->where('project_type_id', $this->projectType->id);
                });
            }])->get();
        });
    }

    #[Computed]
    public function versionTagGroups()
    {
        // TODO: refactor this
        $cacheKey = 'project_version_tag_groups_by_type_'.$this->projectType->value;

        return Cache::remember($cacheKey, now()->addHours(24), function () {
            return ProjectVersionTagGroup::whereHas('projectTypes', function ($query) {
                $query->where('project_type_id', $this->projectType->id);
            })->with(['tags' => function ($query) {
                $query->whereHas('projectTypes', function ($subQuery) {
                    $subQuery->where('project_type_id', $this->projectType->id);
                });
            }])->get();
        });
    }

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
}
