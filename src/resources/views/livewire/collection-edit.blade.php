<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="mb-6 flex items-center justify-between gap-3 flex-wrap">
        <x-button icon="lucide-arrow-left" label="Back to Collection"
            link="{{ route('collection.show', $collection) }}" class="btn-ghost" />
    </div>

    <x-card title="Edit Collection" separator class="mb-6">
        <div class="space-y-4">
            <x-input wire:model="name" label="Name" />

            <x-select
                wire:model="visibility"
                label="Visibility"
                :options="$this->visibilityOptions"
                option-value="id"
                option-label="name"
            />

            <x-textarea wire:model="description" label="Description (Markdown)" rows="6" />

            <div class="flex justify-end">
                <x-button label="Save metadata" icon="lucide-save" wire:click="saveMetadata" class="btn-primary" />
            </div>
        </div>
    </x-card>

    <x-card title="Entries" separator>
        <div class="space-y-4">
            @forelse ($collection->entries as $entry)
                <div class="border border-base-300 rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        @if ($entry->project)
                            <div class="min-w-0">
                                <a href="{{ route('project.show', ['projectType' => $entry->project->projectType, 'project' => $entry->project]) }}"
                                    class="text-lg font-semibold text-primary hover:text-primary-focus">
                                    {{ $entry->project->pretty_name ?? $entry->project->name }}
                                </a>
                                <p class="text-sm text-base-content/70">{{ $entry->project->summary }}</p>
                            </div>
                        @else
                            <div>
                                <h3 class="font-semibold text-error">Unavailable project</h3>
                                <p class="text-sm text-base-content/70">This project was deleted or is no longer available.</p>
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <x-button icon="lucide-arrow-up" class="btn-ghost btn-sm"
                                wire:click="moveEntryUp('{{ $entry->uid }}')" />
                            <x-button icon="lucide-arrow-down" class="btn-ghost btn-sm"
                                wire:click="moveEntryDown('{{ $entry->uid }}')" />
                            <x-button icon="lucide-trash-2" class="btn-ghost btn-sm text-error"
                                wire:click="removeEntry('{{ $entry->uid }}')"
                                wire:confirm="Remove this entry from the collection?" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <x-textarea
                            wire:model="entryNotes.{{ $entry->uid }}"
                            label="Entry note"
                            rows="3"
                        />
                        <div class="flex justify-end">
                            <x-button label="Save note" icon="lucide-save" class="btn-primary btn-sm"
                                wire:click="saveEntryNote('{{ $entry->uid }}')" />
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-base-content/60">This collection has no entries yet.</p>
            @endforelse
        </div>
    </x-card>
</div>

