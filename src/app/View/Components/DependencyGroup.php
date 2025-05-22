<?php

namespace App\View\Components;

use App\Models\Project;
use App\Models\ProjectVersion;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class DependencyGroup extends Component
{
    /**
     * The title of the dependency group.
     *
     * @var string
     */
    public $title;

    /**
     * The dependencies to display.
     *
     * @var Collection
     */
    public $dependencies;

    /**
     * The project that owns the version.
     *
     * @var Project
     */
    public $project;

    /**
     * The version that has the dependencies.
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
     * @param string $title
     * @param Collection $dependencies
     * @param Project $project
     * @param ProjectVersion $version
     * @param string $badgeColor
     * @return void
     */
    public function __construct(string $title, Collection $dependencies, Project $project, ProjectVersion $version, string $badgeColor)
    {
        $this->title = $title;
        $this->dependencies = $dependencies;
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
        return view('components.dependency-group');
    }
}
