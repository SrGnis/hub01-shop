<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectShowVersions extends Component
{
    use WithPagination;

    public Project $project;

    public int $perPage = 10;

    public function mount(Project $project)
    {
        logger('ProjectShowVersions mount called', [
            'project_id' => $project->id,
            'project_slug' => $project->slug,
        ]);

        $this->project = $project;
    }

    public function updatedPerPage()
    {
        logger('ProjectShowVersions perPage updated to: '.$this->perPage);
        $this->resetPage();
    }

    public function render()
    {
        logger('ProjectShowVersions render called', [
            'project_id' => $this->project->id,
        ]);

        $versions = $this->project->versions()
            ->with('tags.tagGroup')
            ->orderBy('release_date', 'desc')
            ->paginate($this->perPage);

        return view('livewire.project-show-versions', [
            'versions' => $versions,
        ]);
    }
}

