<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectShowVersions extends Component
{
    use WithPagination;

    public Project $project;

    public $perPage = 10;

    public $perPageOptions = [
        ['id' => 10, 'name' => '10'],
        ['id' => 25, 'name' => '25'],
        ['id' => 50, 'name' => '50'],
        ['id' => 100, 'name' => '100'],
    ];

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
