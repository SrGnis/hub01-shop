<x-platform.shell>
    <x-slot:header>
        <x-header
            title="Collections"
            icon="lucide-folder-open"
            icon-classes="w-6 h-6"
            subtitle="Manage your collections, visibility, and entries."
            class="!mb-0"
        >
            <x-slot:actions class="w-full justify-end md:w-auto">
                <div x-data="{ showFilters: false }" class="w-full md:w-auto">
                    <div class="md:hidden mb-3 w-full flex justify-end">
                        <x-button
                            type="button"
                            class="btn-ghost btn-sm"
                            icon="lucide-sliders-horizontal"
                            label="Filters"
                            @click="showFilters = !showFilters"
                            ::aria-expanded="showFilters.toString()"
                        />
                    </div>

                    <div
                        class="grid grid-cols-1 md:grid-cols-4 gap-4"
                        x-show="showFilters || window.matchMedia('(min-width: 768px)').matches"
                        x-transition
                    >
                        <div class="md:col-span-2">
                            <x-input
                                label="Search"
                                placeholder="Search by name or description..."
                                icon="lucide-search"
                                wire:model.live.debounce.300ms="search"
                                clearable
                            />
                        </div>

                        <x-select
                            label="Visibility"
                            wire:model.live="visibility"
                            :options="$this->visibilityOptions"
                        />

                        <x-select
                            label="Per page"
                            wire:model.live="perPage"
                            :options="[
                                ['id' => 10, 'name' => '10'],
                                ['id' => 25, 'name' => '25'],
                                ['id' => 50, 'name' => '50'],
                            ]"
                        />
                    </div>
                </div>
            </x-slot:actions>
        </x-header>
    </x-slot:header>

    <x-slot:left>
        <x-platform.section-nav current="collections" />
    </x-slot:left>

    @php
        $headers = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'visibility', 'label' => 'Visibility', 'class' => 'hidden lg:table-cell'],
            ['key' => 'projects', 'label' => 'Projects', 'class' => 'hidden lg:table-cell text-right'],
        ];
    @endphp

    <x-card aria-live="polite">
        @if ($this->collections->count() === 0)
            <div class="text-center py-12">
                <x-icon name="lucide-folder-open" class="w-14 h-14 mx-auto mb-4 text-base-content/40" />
                <h3 class="text-lg font-medium mb-2">No collections found</h3>
                <p class="text-sm text-base-content/60">Try adjusting filters or create collections from project pages.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <x-table :headers="$headers" :rows="$this->collections" class="table-zebra" aria-label="Collections list">
                    @scope('cell_name', $collection)
                        <div class="font-medium">
                            <a href="{{ route('collection.show', ['collection' => $collection]) }}" class="font-medium link link-hover">
                                {{ $collection->name }}
                            </a>

                            @if (!empty($collection->description))
                                <p class="text-xs text-base-content/60 line-clamp-2 mt-1">{{ $collection->description }}</p>
                            @endif

                            <div class="lg:hidden text-xs text-base-content/70 flex flex-wrap items-center gap-2 mt-2">
                                <x-badge
                                    :value="ucfirst((string) $collection->visibility?->value)"
                                    class="badge-xs {{ $collection->isPublic() ? 'badge-success' : ($collection->isHidden() ? 'badge-warning' : 'badge-info') }}"
                                />
                                <span>Projects: {{ number_format((int) ($collection->projects_count ?? 0)) }}</span>                            </div>
                        </div>
                    @endscope

                    @scope('cell_visibility', $collection)
                        <x-badge
                            :value="ucfirst((string) $collection->visibility?->value)"
                            class="badge-sm {{ $collection->isPublic() ? 'badge-success' : ($collection->isHidden() ? 'badge-warning' : 'badge-info') }}"
                        />
                    @endscope

                    @scope('cell_projects', $collection)
                        <div class="text-right">{{ number_format((int) ($collection->projects_count ?? 0)) }}</div>
                    @endscope

                    @scope('actions', $collection)
                        <div class="flex items-center justify-end gap-2">
                            @if (!$collection->isSystem())
                                <x-button
                                    icon="settings"
                                    class="btn-ghost btn-sm"
                                    link="{{ route('collection.edit', ['collection' => $collection]) }}"
                                    aria-label="Collection {{ $collection->name }} settings"
                                />
                            @endif
                        </div>
                    @endscope
                </x-table>
            </div>

            <div class="mt-4">
                {{ $this->collections->links() }}
            </div>
        @endif
    </x-card>
</x-platform.shell>
