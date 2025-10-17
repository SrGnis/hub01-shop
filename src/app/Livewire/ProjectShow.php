<?php

namespace App\Livewire;

use Livewire\Attributes\Url;
use App\Models\Project;
use Livewire\Component;

class ProjectShow extends Component
{
    public Project $project;

    #[Url]
    public string $activeTab = 'description';

    public function mount(Project $project, ?string $activeTab = null)
    {
        $this->project = $project;

        if ($activeTab && in_array($activeTab, ['description', 'versions', 'changelog'])) {
            $this->activeTab = $activeTab;
        }
    }

    public function render()
    {
        return view('livewire.project-show');
    }
}

