<?php

namespace App\Livewire;

use App\Models\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CollectionShow extends Component
{
    public Collection $collection;

    public function mount(?Collection $collection = null, ?string $token = null): void
    {
        if ($token !== null) {
            $this->collection = Collection::query()
                ->hiddenToken($token)
                ->with(['user', 'entries.project'])
                ->firstOrFail();

            Gate::authorize('collections.view.hidden-token', [$this->collection, $token]);

            return;
        }

        abort_if($collection === null, 404);

        $this->collection = Collection::query()
            ->where('uid', $collection->uid)
            ->with(['user', 'entries.project'])
            ->firstOrFail();

        Gate::authorize('view', $this->collection);
    }

    public function render()
    {
        /** @disregard P1013 */
        return view('livewire.collection-show')
            ->title($this->collection->name);
    }
}
