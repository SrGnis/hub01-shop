<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectShowChangelog extends Component
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
            ->whereNotNull('changelog')
            ->orderBy('release_date', 'desc')
            ->paginate($this->perPage);

        return view('livewire.project-show-changelog', [
            'versions' => $versions,
        ]);
    }
}

