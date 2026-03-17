@props([
    'targetProjectName' => '',
    'availableCollections' => collect(),
    'quickCollectionNameModel' => 'quickCollectionName',
    'addAction' => 'addProjectToCollection',
    'quickCreateAction' => 'quickCreateCollectionAndAttach',
])

<x-modal {{ $attributes }} title="Add to Collection" separator>
    @if ($targetProjectName !== '')
        <p class="text-sm text-base-content/70 mb-4">
            Select a collection for <span class="font-semibold">{{ $targetProjectName }}</span>.
        </p>
    @endif

    <div class="space-y-2 mb-6 max-h-64 overflow-y-auto">
        @forelse ($availableCollections as $collection)
            <x-button
                label="{{ $collection->name }}"
                icon="lucide-folder"
                wire:click="{{ $addAction }}('{{ $collection->uid }}')"
                class="btn-ghost w-full justify-start"
            />
        @empty
            <p class="text-sm text-base-content/60">No collections yet. Create one below.</p>
        @endforelse
    </div>

    <div class="border-t pt-4">
        <h4 class="font-semibold mb-2">Quick create private collection</h4>
        <div class="flex gap-2">
            <x-input wire:model="{{ $quickCollectionNameModel }}" placeholder="Collection name" class="w-full" />
            <x-button label="Create" icon="lucide-plus" wire:click="{{ $quickCreateAction }}" class="btn-primary" />
        </div>
    </div>
</x-modal>
