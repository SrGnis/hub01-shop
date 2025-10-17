<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectShowDescription extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function render()
    {
        return view('livewire.project-show-description');
    }
}

