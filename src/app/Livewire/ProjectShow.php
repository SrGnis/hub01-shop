<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\Project;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectShow extends Component
{
    use WithPagination;

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

        if ($activeTab && in_array($activeTab, ['description', 'versions', 'changelog'])) {
            $this->activeTab = $activeTab;
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
        return Project::where('slug', $this->projectSlug)->firstOrFail();
    }

    #[Computed]
    public function versions()
    {
        return $this->project->versions()
            ->with('tags.tagGroup')
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

