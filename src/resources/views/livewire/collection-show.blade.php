<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="mb-6 flex items-center justify-between gap-3 flex-wrap">
        <x-button icon="lucide-arrow-left" label="Back to Profile"
            link="{{ route('user.profile', $collection->user) }}" class="btn-ghost" />

        @auth
            @if (auth()->id() === $collection->user_id && !$collection->isSystem())
                <x-button icon="lucide-pencil" label="Edit Collection"
                    link="{{ route('collection.edit', $collection) }}" class="btn-primary" />
            @endif
        @endauth
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
                <x-badge value="{{ ucfirst($collection->visibility->value) }}" class="badge-outline" />
                <x-badge value="{{ $collection->entries->count() }} items" class="badge-primary" />
            </div>
        </div>

        @if ($collection->description)
            <div class="mt-6">
                <x-markdown class="prose max-w-none dark:prose-invert">{!! $collection->description !!}</x-markdown>
            </div>
        @endif
    </x-card>

    <x-card title="Entries" separator>
        <div class="space-y-4">
            @forelse ($collection->entries as $entry)
                <div class="border border-base-300 rounded-lg p-4">
                    @if ($entry->project)
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <a href="{{ route('project.show', ['projectType' => $entry->project->projectType, 'project' => $entry->project]) }}"
                                    class="text-lg font-semibold text-primary hover:text-primary-focus">
                                    {{ $entry->project->pretty_name ?? $entry->project->name }}
                                </a>
                                <p class="text-sm text-base-content/70">{{ $entry->project->summary }}</p>
                            </div>
                            <x-badge value="#{{ $entry->sort_order + 1 }}" class="badge-ghost" />
                        </div>
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
                        <div class="mt-3 bg-base-200 rounded p-3 text-sm">
                            {{ $entry->note }}
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-base-content/60">This collection has no entries yet.</p>
            @endforelse
        </div>
    </x-card>
</div>

