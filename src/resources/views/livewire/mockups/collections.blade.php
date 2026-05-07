@php
    $headers = [
        ['key' => 'icon', 'label' => 'Icon', 'class' => 'w-16'],
        ['key' => 'name', 'label' => 'Name'],
        ['key' => 'projects_count', 'label' => 'Projects'],
        ['key' => 'type', 'label' => 'Type'],
    ];

    $collections = collect([
        ['id' => 1, 'icon' => 'lucide-heart', 'name' => 'Favorites', 'projects_count' => 24, 'type' => 'System'],
        ['id' => 2, 'icon' => 'lucide-folder-open', 'name' => 'PvP Balance Pack', 'projects_count' => 12, 'type' => 'Private'],
        ['id' => 3, 'icon' => 'lucide-package', 'name' => 'QoL Essentials', 'projects_count' => 8, 'type' => 'Public'],
        ['id' => 4, 'icon' => 'lucide-star', 'name' => 'Top Picks', 'projects_count' => 15, 'type' => 'Unlisted'],
    ]);
@endphp

<x-layouts.two-column-base>
    <x-slot:header>
        <x-header title="Collections" icon="folder-open" icon-classes="w-6 h-6" subtitle="Manage your personal and shared collections, review project counts, and jump to edit quickly.">
            <x-slot:actions>
                <x-input placeholder="Search collections" icon="lucide-search" class="w-full lg:w-96" />

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
                        <x-menu-item title="System" />
                        <x-menu-item title="Public" />
                        <x-menu-item title="Private" />
                        <x-menu-item title="Unlisted" />
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
                <x-table :headers="$headers" :rows="$collections" class="table-sm" container-class="overflow-visible">
                    @scope('cell_icon', $collection)
                        <div class="flex items-center justify-center">
                            <x-icon :name="$collection['icon']" class="w-7 h-7 text-primary" />
                        </div>
                    @endscope

                    @scope('cell_name', $collection)
                        <span class="font-medium text-lg">{{ $collection['name'] }}</span>
                    @endscope

                    @scope('cell_projects_count', $collection)
                        <span class="text-lg">{{ number_format($collection['projects_count']) }}</span>
                    @endscope

                    @scope('cell_type', $collection)
                        <x-badge :value="$collection['type']" class="badge-soft badge-primary" />
                    @endscope

                    @scope('actions', $collection)
                        <x-button
                            icon="lucide-cog"
                            class="btn-ghost btn-sm"
                            tooltip="Open edit page"
                            link="#"
                        />
                    @endscope
                </x-table>
            </x-card>
        </div>
    </x-slot:right>
</x-layouts.two-column-base>
