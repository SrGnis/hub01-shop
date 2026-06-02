<?php

namespace App\Livewire;

use App\Models\Collection;
use App\Services\CollectionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Mary\Traits\Toast;

class CollectionShow extends Component
{
    use Toast;

    public Collection $collection;

    private CollectionService $collectionService;

    public function boot(CollectionService $collectionService): void
    {
        $this->collectionService = $collectionService;
    }

    public function mount(?Collection $collection = null, ?string $token = null): void
    {
        if ($token !== null) {
            $this->collection = $this->collectionService->getHiddenByToken($token);

            abort_if($this->collection === null, 404);

            // Apply custom eager loads for stats and relations
            $this->collection->load([
                'entries.project' => fn ($query) => $query->withStats()->withRelations(),
            ]);

            Gate::authorize('collections.view.hidden-token', [$this->collection, $token]);

            return;
        }

        abort_if($collection === null, 404);

        $this->collection = $this->collectionService->getByUidWithEntries($collection->uid);

        abort_if($this->collection === null, 404);

        // Apply custom eager loads for stats and relations
        $this->collection->load([
            'entries.project' => fn ($query) => $query->withStats()->withRelations(),
        ]);

        Gate::authorize('view', $this->collection);
    }

    public function render()
    {
        /** @disregard P1013 */
        return view('livewire.collection-show')
            ->title($this->collection->name);
    }

    public function deleteCollection(): void
    {
        Gate::authorize('update', $this->collection);

        try {
            $this->collectionService->deleteCollection($this->collection);
            $this->success('Collection deleted successfully.', redirectTo: route('user.profile', Auth::user()));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }
}
