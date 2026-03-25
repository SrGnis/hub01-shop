@props([
    'collection',
    'entryCount' => null,
    'showOwner' => false,
    'showSystemBadge' => true,
    'descriptionLines' => 2,
])

@php
    $resolvedEntryCount = $entryCount
        ?? $collection->entries_count
        ?? ($collection->relationLoaded('entries') ? $collection->entries->count() : $collection->entries()->count());

    $visibilityValue = $collection->visibility?->value ?? $collection->visibility;
    $showVisibilityBadge = auth()->check() && (int) auth()->id() === (int) $collection->user_id;
    $isFavoritesCollection = method_exists($collection, 'isFavoritesSystemCollection') && $collection->isFavoritesSystemCollection();
    $previewProjects = $collection->relationLoaded('entries')
        ? $collection->entries
            ->pluck('project')
            ->filter()
            ->take(10)
        : collect();
@endphp

<x-card class="cursor-pointer hover:bg-base-100/90 transition-colors"
    onclick="window.location='{{ route('collection.show', $collection) }}'">
    <div class="flex items-start justify-between gap-4">
        <div class="space-y-2 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                @if ($isFavoritesCollection)
                    <x-icon name="lucide-heart" class="w-4 h-4 text-error" />
                @endif

                <span class="text-lg font-semibold text-primary">
                    {{ $collection->name }}
                </span>

                @if ($showVisibilityBadge)
                    <x-badge value="{{ ucfirst($visibilityValue) }}" class="badge-outline badge-sm" />
                @endif
                <x-badge value="{{ $resolvedEntryCount }} items" class="badge-ghost badge-sm" />
            </div>

            @if ($showOwner)
                <p class="text-sm text-base-content/70">
                    by {{ $collection->user->name ?? 'Unknown' }}
                </p>
            @endif

            <div class="overflow-hidden">
                @if ($previewProjects->isNotEmpty())
                    <div class="flex items-center gap-2 whitespace-nowrap overflow-hidden">
                        @foreach ($previewProjects as $project)
                            <div class="inline-flex items-center gap-2.5 px-2.5 py-1.5 rounded-md bg-base-200 max-w-[220px]">
                                <img
                                    src="{{ $project->getLogoUrl() ?? '/images/default-project.png' }}"
                                    class="w-6 h-6 rounded object-cover shrink-0"
                                    alt="{{ $project->name }} Logo"
                                >
                                <span class="text-sm truncate">
                                    {{ $project->pretty_name ?? $project->name }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-base-content/60">No projects yet.</p>
                @endif
            </div>
        </div>

    </div>
</x-card>
