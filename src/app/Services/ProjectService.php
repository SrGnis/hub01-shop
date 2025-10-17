<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectTagGroup;
use App\Models\ProjectType;
use App\Models\ProjectVersionTagGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProjectService
{
    /**
     * Search and filter projects based on criteria
     */
    public function searchProjects(
        ProjectType $projectType,
        string $search = '',
        array $selectedTags = [],
        array $selectedVersionTags = [],
        string $orderBy = 'downloads',
        string $orderDirection = 'desc',
        int $resultsPerPage = 10
    ): LengthAwarePaginator {
        $projects = Project::where('name', 'like', '%' . $search . '%')
            ->where('project_type_id', $projectType->id);

        // Filter by project tags
        if (count($selectedTags)) {
            $projects->whereHas('tags', function ($query) use ($selectedTags) {
                $query->whereIn('tag_id', $selectedTags);
            }, '>=', count($selectedTags));
        }

        // Filter by version tags
        if (count($selectedVersionTags)) {
            $projects->whereHas('versions.tags', function ($query) use ($selectedVersionTags) {
                $query->whereIn('tag_id', $selectedVersionTags);
            });
        }

        // Apply ordering based on selected option
        $projects = $this->applyOrdering($projects, $orderBy, $orderDirection);

        return $projects->paginate($resultsPerPage);
    }

    /**
     * Apply ordering to the query
     */
    private function applyOrdering(Builder $query, string $orderBy, string $orderDirection): Builder
    {
        switch ($orderBy) {
            case 'name':
                return $query->orderBy('name', $orderDirection);
            case 'created_at':
                return $query->orderBy('created_at', $orderDirection);
            case 'latest_version':
                return $query->orderBy('recent_release_date', $orderDirection);
            case 'downloads':
            default:
                return $query->orderBy('downloads', $orderDirection);
        }
    }

    /**
     * Get tag groups for a project type with caching
     */
    public function getTagGroups(ProjectType $projectType): Collection
    {
        $cacheKey = 'project_tag_groups_by_type_' . $projectType->value;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($projectType) {
            return ProjectTagGroup::whereHas('projectTypes', function ($query) use ($projectType) {
                $query->where('project_type_id', $projectType->id);
            })->with(['tags' => function ($query) use ($projectType) {
                $query->whereHas('projectTypes', function ($subQuery) use ($projectType) {
                    $subQuery->where('project_type_id', $projectType->id);
                });
            }])->get();
        });
    }

    /**
     * Get version tag groups for a project type with caching
     */
    public function getVersionTagGroups(ProjectType $projectType): Collection
    {
        $cacheKey = 'project_version_tag_groups_by_type_' . $projectType->value;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($projectType) {
            return ProjectVersionTagGroup::whereHas('projectTypes', function ($query) use ($projectType) {
                $query->where('project_type_id', $projectType->id);
            })->with(['tags' => function ($query) use ($projectType) {
                $query->whereHas('projectTypes', function ($subQuery) use ($projectType) {
                    $subQuery->where('project_type_id', $projectType->id);
                });
            }])->get();
        });
    }

    /**
     * Get order options for the search interface
     */
    public function getOrderOptions(): array
    {
        return [
            ['id' => 'name', 'name' => 'Project Name', 'icon' => 'lucide-text'],
            ['id' => 'created_at', 'name' => 'Creation Date', 'icon' => 'lucide-calendar'],
            ['id' => 'latest_version', 'name' => 'Update Date', 'icon' => 'lucide-refresh-cw'],
            ['id' => 'downloads', 'name' => 'Downloads', 'icon' => 'lucide-download'],
        ];
    }

    /**
     * Get direction options for the search interface
     */
    public function getDirectionOptions(): array
    {
        return [
            ['id' => 'asc', 'name' => 'Ascending', 'icon' => 'lucide-arrow-up'],
            ['id' => 'desc', 'name' => 'Descending', 'icon' => 'lucide-arrow-down'],
        ];
    }

    /**
     * Get per page options for the search interface
     */
    public function getPerPageOptions(): array
    {
        return [
            ['id' => 10, 'name' => '10'],
            ['id' => 25, 'name' => '25'],
            ['id' => 50, 'name' => '50'],
            ['id' => 100, 'name' => '100'],
        ];
    }
}
