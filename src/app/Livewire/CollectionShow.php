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

    public function mount(?Collection $collection = null, ?string $token = null): void
    {
        if ($token !== null) {
            $this->collection = Collection::query()
                ->hiddenToken($token)
                ->with([
                    'user',
                    'entries.project' => fn ($query) => $query->withStats()->withRelations(),
                ])
                ->firstOrFail();

            Gate::authorize('collections.view.hidden-token', [$this->collection, $token]);

            return;
        }

        abort_if($collection === null, 404);

        $this->collection = Collection::query()
            ->where('uid', $collection->uid)
            ->with([
                'user',
                'entries.project' => fn ($query) => $query->withStats()->withRelations(),
            ])
            ->firstOrFail();

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
            app(CollectionService::class)->deleteCollection($this->collection);
            $this->success('Collection deleted successfully.', redirectTo: route('user.profile', Auth::user()));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }
}
