<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectVersion;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProjectVersionShow extends Component
{
    public string $projectSlug;
    public string $versionKey;

    public function mount($project, $version_key)
    {
        $this->projectSlug = $project;
        $this->versionKey = $version_key;
    }

    #[Computed]
    public function project()
    {
        return Project::where('slug', $this->projectSlug)
            ->with(['owner', 'tags.tagGroup'])
            ->firstOrFail();
    }

    #[Computed]
    public function version()
    {
        $version = $this->project->versions()
            ->where('version', $this->versionKey)
            ->with([
                'files',
                'dependencies.dependencyProjectVersion.project',
                'dependencies.dependencyProject',
                'tags.tagGroup',
            ])
            ->firstOrFail();

        abort_if($version->project_id !== $this->project->id, 404);

        return $version;
    }

    public function render()
    {
        return view('livewire.project-version-show', [
            'project' => $this->project,
            'version' => $this->version,
        ]);
    }
}

