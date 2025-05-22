<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectVersion;
use Livewire\Component;

class ProjectVersionShow extends Component
{
    public Project $project;
    public string $version_key;
    public ProjectVersion $version;

    public function mount(Project $project, string $version_key)
    {
        $this->project = $project;
        $this->version_key = $version_key;
        $this->version = $this->project->versions()->where('version', $version_key)->get()->first();

        if ($this->version->project_id !== $this->project->id) {
            abort(404);
        }

        $this->project->load(['owner', 'tags.tagGroup']);
        $this->version->load([
            'files',
            'dependencies.dependencyProjectVersion.project',
            'dependencies.dependencyProject',
            'tags.tagGroup'
        ]);
    }

    public function render()
    {
        return view('livewire.project-version-show');
    }
}
