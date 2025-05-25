<?php

namespace App\View\Components;

use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDependency;
use Illuminate\View\Component;

class DependencyItem extends Component
{
    /**
     * The dependency to display.
     *
     * @var ProjectVersionDependency
     */
    public $dependency;

    /**
     * The project that owns the version.
     *
     * @var Project
     */
    public $project;

    /**
     * The version that has the dependency.
     *
     * @var ProjectVersion
     */
    public $version;

    /**
     * The badge color for the dependency type.
     *
     * @var string
     */
    public $badgeColor;

    /**
     * Create a new component instance.
     *
     * @param  string  $badgeColor
     * @return void
     */
    public function __construct(ProjectVersionDependency $dependency, Project $project, ProjectVersion $version, $badgeColor = 'bg-red-700')
    {
        $this->dependency = $dependency;
        $this->project = $project;
        $this->version = $version;
        $this->badgeColor = $badgeColor;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.dependency-item');
    }
}
