<div class="container mx-auto px-4 py-8 max-w-7xl" x-data="{ showShareLinkModal: false }">
    <livewire:report-abuse />

    <div class="mb-6 flex items-center justify-between gap-3 flex-wrap">
        <x-button icon="lucide-arrow-left" label="Back to Profile"
            link="{{ route('user.profile', $collection->user) }}" class="btn-ghost" />

        <div class="flex items-center gap-2">
            @auth
                @if (auth()->id() === $collection->user_id && !$collection->isSystem())
                    <x-button icon="lucide-pencil" label="Edit Collection"
                        link="{{ route('collection.edit', $collection) }}" class="btn-primary" />
                @endif

                @if (auth()->id() === $collection->user_id && $collection->isHidden() && filled($collection->hidden_share_token))
                    <x-button icon="lucide-link" label="Share Link" class="btn-outline"
                        @click="showShareLinkModal = true; $nextTick(() => { $refs.shareUrlField?.focus(); $refs.shareUrlField?.select(); })" />
                @endif
            @endauth

            <x-dropdown right>
                <x-slot:trigger>
                    <x-button icon="ellipsis" class="btn-ghost" />
                </x-slot:trigger>

                <x-menu-item
                    title="Report"
                    class="text-error"
                    icon="flag"
                    @click="$dispatch('open-report-modal', { itemId: '{{ $collection->uid }}', itemType: '{{ addslashes(\App\Models\Collection::class) }}', itemName: '{{ addslashes($collection->name) }}' })"
                />

                @auth
                    @if (auth()->id() === $collection->user_id && !$collection->isSystem())
                        <x-menu-item
                            title="Delete"
                            icon="lucide-trash-2"
                            class="text-error"
                            wire:click="deleteCollection"
                            wire:confirm="Delete this collection?"
                        />
                    @endif
                @endauth
            </x-dropdown>
        </div>
    </div>

    <x-card class="mb-6">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-3xl font-bold">{{ $collection->name }}</h1>
                <p class="text-sm text-base-content/70 mt-1">
                    by {{ $collection->user->name }}
                </p>
            </div>

            <div class="flex gap-2">
                @if (auth()->check() && auth()->id() === $collection->user_id)
                    <x-badge value="{{ ucfirst($collection->visibility->value) }}" class="badge-outline" />
                @endif
                <x-badge value="{{ $collection->entries->count() }} items" class="badge-primary" />
            </div>
        </div>

        @if ($collection->description)
            <div class="mt-6">
                <x-markdown class="prose max-w-none dark:prose-invert">{!! $collection->description !!}</x-markdown>
            </div>
        @endif
    </x-card>

    @auth
        @if (auth()->id() === $collection->user_id && $collection->isHidden() && filled($collection->hidden_share_token))
            <x-modal x-show="showShareLinkModal" title="Share Collection Link" separator>
                <div class="space-y-3">
                    <p class="text-sm text-base-content/70">
                        Copy and share this private collection URL.
                    </p>

                    <x-textarea x-ref="shareUrlField" readonly rows="3" class="font-mono text-xs">{{ route('collection.hidden.show', ['token' => $collection->hidden_share_token]) }}</x-textarea>
                </div>

                <x-slot:actions>
                    <x-button label="Close" class="btn-primary" @click="showShareLinkModal = false" />
                </x-slot:actions>
            </x-modal>
        @endif
    @endauth

    <div>
        @forelse ($collection->entries as $entry)
            <div class="rounded-lg px-4 pb-4">
                    @if ($entry->project)
                        <x-project-card :project="$entry->project" />
                    @else
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-error">Unavailable project</h3>
                                <p class="text-sm text-base-content/70">This project was deleted or is no longer available.</p>
                            </div>
                            <x-icon name="lucide-ban" class="w-5 h-5 text-error" />
                        </div>
                    @endif

                    @if ($entry->note)
                        <div class="bg-base-300 rounded-lg p-3 my-2 text-sm">
                            <div class="mb-2 flex gap-2">
                                <x-icon name="notepad-text"/>
                                <span class="font-bold">Note:</span>
                            </div>
                            <div class="ml-5">
                                <p>{{ $entry->note }}</p>
                            </div>
                        </div>
                    @endif
            </div>
        @empty
            <p class="text-base-content/60">This collection has no entries yet.</p>
        @endforelse
    </div>
</div>
