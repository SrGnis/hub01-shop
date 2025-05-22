<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectShow extends Component
{
    public Project $project;
    public $activeTab = 'description';

    protected $queryString = ['activeTab'];

    public function mount(Project $project, $activeTab = null)
    {
        $this->project = $project;

        if ($activeTab && in_array($activeTab, ['description', 'versions', 'changelog'])) {
            $this->activeTab = $activeTab;
        }

        $this->project->load(['owner', 'tags.tagGroup', 'versions' => function ($query) {
            $query->orderBy('release_date', 'desc')->limit(5);
        }, 'versions.tags.tagGroup']);
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.project-show');
    }
}
