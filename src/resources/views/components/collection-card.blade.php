@props([
    'collection',
    'entryCount' => null,
    'showOwner' => false,
    'showSystemBadge' => true,
    'descriptionLines' => 2,
])
@php
    $resolvedEntryCount = $entryCount ?? $collection->entries_count
        ?? ($collection->relationLoaded('entries') ? $collection->entries->count() : null);

    $visibilityLabel = $collection->visibility_label ?? ucfirst($collection->visibility?->value ?? $collection->visibility);
    $isPrivate = in_array($collection->visibility?->value ?? $collection->visibility, ['private', 'hidden']);

    $showVisibilityBadge = auth()->check() && (int) auth()->id() === (int) $collection->user_id;
    $isFavoritesCollection = method_exists($collection, 'isFavoritesSystemCollection') && $collection->isFavoritesSystemCollection();

    $previewProjects = $collection->relationLoaded('entries')
        ? $collection->entries->pluck('project')->filter()->take(4)
        : collect();

    $extraCount = $resolvedEntryCount !== null ? max(0, $resolvedEntryCount - 4) : 0;
@endphp

<a href="{{ route('collection.show', $collection) }}" class="block group">
    <x-card class="transition-colors group-hover:bg-base-100/90">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-2 min-w-0 w-full">

                {{-- Title row --}}
                <div class="flex items-center gap-2 flex-wrap">
                    @if ($isFavoritesCollection)
                        <x-icon name="lucide-heart" class="w-4 h-4 text-error shrink-0" />
                    @endif
                    <span class="text-lg font-semibold text-primary truncate">
                        {{ $collection->name }}
                    </span>
                    @if ($showVisibilityBadge)
                        <x-badge
                            value="{{ $visibilityLabel }}"
                            class="badge-soft badge-sm shrink-0 {{ $isPrivate ? 'badge-warning' : '' }}"
                        />
                    @endif
                    @if ($resolvedEntryCount !== null)
                        <x-badge value="{{ $resolvedEntryCount }} items" class="badge-neutral badge-sm shrink-0" />
                    @endif
                </div>

                {{-- Owner --}}
                @if ($showOwner)
                    <p class="text-sm text-base-content/70">
                        by {{ $collection->user->name ?? 'Unknown' }}
                    </p>
                @endif

                {{-- Description --}}
                @if ($collection->description)
                    <p class="text-sm text-base-content/70 line-clamp-{{ $descriptionLines }}">
                        {{ $collection->description }}
                    </p>
                @endif

                {{-- Project previews --}}
                <div class="overflow-hidden">
                    @if ($previewProjects->isNotEmpty())
                        <div class="flex items-center gap-2 flex-wrap">
                            @foreach ($previewProjects as $project)
                                <div class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-base-200 max-w-[200px] shrink-0">
                                    <img
                                        src="{{ $project->getLogoUrl() ?? asset('images/default-project.png') }}"
                                        onerror="this.src='{{ asset('images/default-project.png') }}'"
                                        class="w-5 h-5 rounded object-cover shrink-0"
                                        alt="{{ $project->name }} logo"
                                    >
                                    <span class="text-sm truncate">
                                        {{ $project->pretty_name ?? $project->name }}
                                    </span>
                                </div>
                            @endforeach
                            @if ($extraCount > 0)
                                <span class="badge badge-ghost badge-sm shrink-0">+{{ $extraCount }} more</span>
                            @endif
                        </div>
                    @else
                        <p class="text-xs text-base-content/60">No projects yet.</p>
                    @endif
                </div>

            </div>
        </div>
    </x-card>
</a>
