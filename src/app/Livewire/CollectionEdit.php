<?php

namespace App\Livewire;

use App\Enums\CollectionVisibility;
use App\Models\Collection;
use App\Services\CollectionService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

class CollectionEdit extends Component
{
    use Toast;

    public Collection $collection;

    public string $name = '';

    public ?string $description = null;

    public string $visibility = 'private';

    /**
     * @var array<string, string|null>
     */
    public array $entryNotes = [];

    public function mount(Collection $collection): void
    {
        $this->collection = Collection::query()
            ->where('uid', $collection->uid)
            ->with(['user', 'entries.project'])
            ->firstOrFail();

        Gate::authorize('update', $this->collection);

        abort_if($this->collection->isSystem(), 404);

        $this->syncFormFromCollection();
    }

    #[Computed]
    public function visibilityOptions(): array
    {
        return [
            ['id' => CollectionVisibility::PUBLIC->value, 'name' => 'Public'],
            ['id' => CollectionVisibility::PRIVATE->value, 'name' => 'Private'],
            ['id' => CollectionVisibility::HIDDEN->value, 'name' => 'Hidden'],
        ];
    }

    public function saveMetadata(): void
    {
        Gate::authorize('update', $this->collection);

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|string|in:public,private,hidden',
        ]);

        app(CollectionService::class)->updateCollection($this->collection, $validated);

        $this->reloadCollection();
        $this->success('Collection metadata updated.');
    }

    public function saveEntryNote(string $entryUid): void
    {
        Gate::authorize('collections.manage.entries', $this->collection);

        app(CollectionService::class)->updateEntryNote(
            $this->collection,
            $entryUid,
            $this->entryNotes[$entryUid] ?? null
        );

        $this->reloadCollection();
        $this->success('Entry note updated.');
    }

    public function removeEntry(string $entryUid): void
    {
        Gate::authorize('collections.manage.entries', $this->collection);

        app(CollectionService::class)->removeEntry($this->collection, $entryUid);

        $this->reloadCollection();
        $this->success('Entry removed from collection.');
    }

    public function moveEntryUp(string $entryUid): void
    {
        $this->moveEntry($entryUid, -1);
    }

    public function moveEntryDown(string $entryUid): void
    {
        $this->moveEntry($entryUid, 1);
    }

    private function moveEntry(string $entryUid, int $direction): void
    {
        Gate::authorize('collections.manage.entries', $this->collection);

        $uids = $this->collection->entries->pluck('uid')->values()->all();
        $index = array_search($entryUid, $uids, true);

        if ($index === false) {
            return;
        }

        $targetIndex = $index + $direction;

        if ($targetIndex < 0 || $targetIndex >= count($uids)) {
            return;
        }

        [$uids[$index], $uids[$targetIndex]] = [$uids[$targetIndex], $uids[$index]];

        app(CollectionService::class)->reorderEntries($this->collection, $uids);

        $this->reloadCollection();
    }

    private function reloadCollection(): void
    {
        $this->collection = Collection::query()
            ->where('uid', $this->collection->uid)
            ->with(['user', 'entries.project'])
            ->firstOrFail();

        $this->syncFormFromCollection();
    }

    private function syncFormFromCollection(): void
    {
        $this->name = $this->collection->name;
        $this->description = $this->collection->description;
        $this->visibility = $this->collection->visibility->value;
        $this->entryNotes = $this->collection->entries
            ->mapWithKeys(fn ($entry) => [$entry->uid => $entry->note])
            ->all();
    }

    public function render()
    {
        /** @disregard P1013 */
        return view('livewire.collection-edit')
            ->title('Edit '.$this->collection->name);
    }
}
