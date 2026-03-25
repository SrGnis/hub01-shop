<?php

namespace App\Livewire\Concerns;

use App\Models\Collection;
use App\Models\Project;
use App\Services\CollectionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

trait InteractsWithProjectCollections
{
    public bool $showCollectionModal = false;

    public ?int $collectionTargetProjectId = null;

    public string $collectionTargetProjectSlug = '';

    public string $collectionTargetProjectName = '';

    public string $quickCollectionName = '';

    #[Computed]
    public function availableCollections()
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return $user->collections()
            ->whereNull('system_type')
            ->when($this->collectionTargetProjectId, function ($query) {
                $query->withExists([
                    'entries as includes_target_project' => function ($entryQuery) {
                        $entryQuery->where('project_id', $this->collectionTargetProjectId);
                    },
                ]);
            })
            ->orderBy('updated_at', 'desc')
            ->orderBy('uid')
            ->get();
    }

    public function toggleFavorite(int $projectId): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->error('Please login to manage favorites.');

            return;
        }

        $project = Project::query()
            ->accessScope()
            ->where('id', $projectId)
            ->firstOrFail();

        $result = app(CollectionService::class)->toggleFavorite($user, $project);

        if ($result['favorited']) {
            $this->success('Project added to favorites.');
        } else {
            $this->info('Project removed from favorites.');
        }

        $this->refreshProjectCollectionCaches();
    }

    public function openAddToCollectionModal(int $projectId): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->error('Please login to manage collections.');

            return;
        }

        $project = Project::query()
            ->accessScope()
            ->where('id', $projectId)
            ->firstOrFail();

        $this->collectionTargetProjectId = $project->id;
        $this->collectionTargetProjectSlug = $project->slug;
        $this->collectionTargetProjectName = $project->name;
        $this->quickCollectionName = '';
        $this->showCollectionModal = true;
    }

    public function addProjectToCollection(string $collectionUid): void
    {
        $user = Auth::user();

        if (! $user || ! $this->collectionTargetProjectId) {
            $this->error('Invalid collection action.');

            return;
        }

        $collection = Collection::query()
            ->where('uid', $collectionUid)
            ->where('user_id', $user->id)
            ->whereNull('system_type')
            ->firstOrFail();

        $project = Project::query()
            ->accessScope()
            ->where('id', $this->collectionTargetProjectId)
            ->firstOrFail();

        try {
            app(CollectionService::class)->addEntry($collection, $project);
        } catch (\RuntimeException $e) {
            $this->warning($e->getMessage());

            return;
        }

        $this->success('Project added to collection.');
        $this->showCollectionModal = false;
        $this->refreshProjectCollectionCaches();
    }

    public function quickCreateCollectionAndAttach(): void
    {
        $user = Auth::user();

        if (! $user || ! $this->collectionTargetProjectId) {
            $this->error('Invalid collection action.');

            return;
        }

        $name = trim($this->quickCollectionName);

        if ($name === '') {
            $this->error('Collection name is required.');

            return;
        }

        $project = Project::query()
            ->accessScope()
            ->where('id', $this->collectionTargetProjectId)
            ->firstOrFail();

        app(CollectionService::class)->quickCreatePrivateCollectionAndAttachProject(
            user: $user,
            name: $name,
            project: $project,
        );

        $this->success('Collection created and project added.');
        $this->quickCollectionName = '';
        $this->showCollectionModal = false;
        $this->refreshProjectCollectionCaches();
    }

    private function refreshProjectCollectionCaches(): void
    {
        foreach (['projects', 'project', 'activeProjects', 'visibleCollections', 'availableCollections'] as $property) {
            if (method_exists($this, $property)) {
                unset($this->{$property});
            }
        }
    }
}
