@php
    $headers = [
        ['key' => 'icon', 'label' => 'Icon', 'class' => 'w-16'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'slug', 'label' => 'Slug'],
        ['key' => 'type', 'label' => 'Type'],
        ['key' => 'status', 'label' => 'Status'],
    ];

    $projects = collect([
        ['id' => 1, 'icon' => 'lucide-puzzle', 'name' => 'Aftershock Redux', 'slug' => 'aftershock-redux', 'type' => 'Mods', 'status' => 'Active'],
        ['id' => 2, 'icon' => 'lucide-map', 'name' => 'Retro Terrain Pack', 'slug' => 'retro-terrain-pack', 'type' => 'Tile Sets', 'status' => 'Draft'],
        ['id' => 3, 'icon' => 'lucide-volume-2', 'name' => 'Dark Ambient SFX', 'slug' => 'dark-ambient-sfx', 'type' => 'Sound Packs', 'status' => 'Pending'],
        ['id' => 4, 'icon' => 'lucide-package', 'name' => 'Survival Addons', 'slug' => 'survival-addons', 'type' => 'Mods', 'status' => 'Active'],
    ]);
@endphp

<x-layouts.two-column-base>
    <x-slot:header>
        <x-header title="Projects" icon="package" icon-classes="w-6 h-6" subtitle="Browse project entries, inspect publication state, and quickly open project actions.">
            <x-slot:actions>
                <x-input placeholder="Search projects" icon="lucide-search" class="w-full lg:w-96" />

                <x-dropdown>
                    <x-slot:trigger>
                        <x-button label="Order" icon="lucide-arrow-up-down" class="btn-ghost" />
                    </x-slot:trigger>
                    <x-menu>
                        <x-menu-item title="Newest first" />
                        <x-menu-item title="Oldest first" />
                        <x-menu-item title="A → Z" />
                        <x-menu-item title="Z → A" />
                    </x-menu>
                </x-dropdown>

                <x-dropdown>
                    <x-slot:trigger>
                        <x-button label="Filter" icon="lucide-list-filter" class="btn-ghost" />
                    </x-slot:trigger>
                    <x-menu>
                        <x-menu-item title="All types" />
                        <x-menu-item title="Mods" />
                        <x-menu-item title="Tile Sets" />
                        <x-menu-item title="Sound Packs" />
                    </x-menu>
                </x-dropdown>
            </x-slot:actions>
        </x-header>
    </x-slot:header>

    <x-slot:left>
        <x-column-navigation :items="$sections" :active="$section" />
    </x-slot:left>

    <x-slot:right>
        <div class="space-y-4 min-h-[28rem]">
            <x-card>
                <x-table :headers="$headers" :rows="$projects" class="table-sm" container-class="overflow-visible">
                    @scope('cell_icon', $project)
                        <div class="flex items-center justify-center">
                            <x-icon :name="$project['icon']" class="w-7 h-7 text-primary" />
                        </div>
                    @endscope

                    @scope('cell_name', $project)
                        <span class="font-medium text-lg">{{ $project['name'] }}</span>
                    @endscope

                    @scope('cell_slug', $project)
                        <span class="font-mono text-sm text-base-content/80">{{ $project['slug'] }}</span>
                    @endscope

                    @scope('cell_type', $project)
                        <x-badge :value="$project['type']" class="badge-soft badge-primary" />
                    @endscope

                    @scope('cell_status', $project)
                        @php
                            $statusClass = match ($project['status']) {
                                'Active' => 'badge-success',
                                'Draft' => 'badge-warning',
                                default => 'badge-info',
                            };
                        @endphp
                        <x-badge :value="$project['status']" class="badge-soft {{ $statusClass }}" />
                    @endscope

                    @scope('actions', $project)
                        <x-button
                            icon="lucide-cog"
                            class="btn-ghost btn-sm"
                            tooltip="Open project actions"
                            link="#"
                        />
                    @endscope
                </x-table>
            </x-card>
        </div>
    </x-slot:right>
</x-layouts.two-column-base>
