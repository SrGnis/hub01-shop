<?php

namespace App\View\Components;

use App\Models\Project;
use App\Models\ProjectVersion;
use Illuminate\View\Component;

class DependenciesSection extends Component
{
    /**
     * The version that has the dependencies.
     *
     * @var ProjectVersion
     */
    public $version;

    /**
     * The project that owns the version.
     *
     * @var Project
     */
    public $project;

    /**
     * Create a new component instance.
     *
     * @param ProjectVersion $version
     * @param Project $project
     * @return void
     */
    public function __construct(ProjectVersion $version, Project $project)
    {
        $this->version = $version;
        $this->project = $project;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.dependencies-section');
    }
}
