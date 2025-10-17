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
        $this->project = $project;
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $versions = $this->project->versions()
            ->with('tags.tagGroup')
            ->orderBy('release_date', 'desc')
            ->paginate($this->perPage);

        return view('livewire.project-show-versions', [
            'versions' => $versions,
        ]);
    }
}

