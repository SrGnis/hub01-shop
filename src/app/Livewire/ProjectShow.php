<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\Project;
use App\Models\Scopes\ProjectFullScope;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ProjectShow extends Component
{
    use WithPagination;
    use Toast;

    #[Locked]
    public string $projectSlug;

    #[Url]
    public string $activeTab = 'description';

    public int $versionsPerPage = 10;
    public int $changelogPerPage = 10;

    public array $sortBy = ['column' => 'release_date', 'direction' => 'desc'];

    public function mount($project, ?string $activeTab = null)
    {
        $this->projectSlug = $project;

        // Check if the project is deactivated
        if ($this->project->isDeactivated()) {

            // use normal laravel flash message toast is not working here
            session()->flash('error', 'This project has been deactivated and cannot be viewed.');
            return redirect()->route('project-search', ['projectType' => $this->project->projectType]);
        }

        if ($activeTab && in_array($activeTab, ['description', 'versions', 'changelog'])) {
            $this->activeTab = $activeTab;
        }else{
            $this->activeTab = 'description';
        }
    }

    public function updatedVersionsPerPage()
    {
        $this->resetPage('versionsPage');
    }

    public function updatedChangelogPerPage()
    {
        $this->resetPage('changelogPage');
    }

    #[Computed]
    public function project()
    {
        /** @disregard P1006, P1005 */
        return Project::accessScope()->where('slug', $this->projectSlug)
            ->firstOrFail();
    }

    #[Computed]
    public function versions()
    {
        return $this->project->versions()
            ->with([
                'tags.tagGroup',
                'project.projectType',
                'project.owner'
            ])
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->versionsPerPage, ['*'], 'versionsPage');
    }

    #[Computed]
    public function changelogVersions()
    {
        return $this->project->versions()
            ->whereNotNull('changelog')
            ->orderBy('release_date', 'desc')
            ->paginate($this->changelogPerPage, ['*'], 'changelogPage');
    }

    public function render()
    {
        return view('livewire.project-show', [
            'project' => $this->project,
            'versions' => $this->versions,
            'changelogVersions' => $this->changelogVersions,
        ]);
    }
}

