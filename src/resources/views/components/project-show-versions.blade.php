@props(['project', 'versions', 'sortBy'])

@php
    $headers = [
        ['key' => 'version_display', 'label' => 'Version', 'sortBy' => 'version'],
        ['key' => 'tags', 'label' => 'Tags', 'class' => 'hidden lg:table-cell', 'sortable' => false],
        ['key' => 'release_date', 'label' => 'Release Date', 'class' => 'hidden lg:table-cell'],
        ['key' => 'downloads', 'label' => 'Downloads', 'class' => 'hidden lg:table-cell'],
    ];
@endphp

<x-card>
    <x-slot:title class="flex justify-between items-center">
        <span>All Versions</span>
        <div class="flex items-center gap-2">
            <label for="versionsPerPage" class="text-sm">Show:</label>
            <x-select
                wire:model.live="versionsPerPage"
                :options="[
                    ['id' => 10, 'name' => '10'],
                    ['id' => 25, 'name' => '25'],
                    ['id' => 50, 'name' => '50'],
                    ['id' => 100, 'name' => '100'],
                ]"
                option-key="id"
                option-value="id"
                option-label="name"
                class="select-sm"
            />
        </div>
    </x-slot:title>

    <x-table :headers="$headers" :rows="$versions" :sort-by="$sortBy" class="table-sm">
        @scope('cell_version_display', $version)
            <div class="flex flex-col gap-1">
                <span class="font-semibold truncate">{{ $version->name }}</span>
                <div class="flex items-center gap-2">
                    <span class="text-sm">{{ $version->version }}</span>
                    <x-badge :value="$version->display_name" class="badge-sm {{ $version->bg_color_class }}" />
                </div>
            </div>
        @endscope

        @scope('cell_tags', $version)
            <div class="flex flex-wrap gap-1">
                @foreach($version->tags as $tag)
                    <x-badge :value="$tag->name" class="badge-sm gap-1 badge-soft">
                        <x-icon :name="$tag->icon" class="w-3 h-3" />
                    </x-badge>
                @endforeach
            </div>
        @endscope

        @scope('cell_release_date', $version)
            <span class="text-sm">{{ $version->release_date->format('M d, Y') }}</span>
        @endscope

        @scope('cell_downloads', $version)
            <span class="text-sm">{{ number_format($version->downloads) }}</span>
        @endscope

        @scope('actions', $version)
            <div class="flex items-center justify-end gap-2">
                @can('editVersion', $version->project)
                    <x-button
                        link="{{ route('project.version.edit', ['projectType' => $version->project->projectType, 'project' => $version->project, 'version_key' => $version]) }}"
                        icon="settings"
                        class="btn-ghost btn-sm"
                    />
                @endcan
                <x-button
                    link="{{ route('project.version.show', ['projectType' => $version->project->projectType, 'project' => $version->project, 'version_key' => $version]) }}"
                    icon="download"
                    class="btn-primary btn-sm"
                />
            </div>
        @endscope

        <x-slot:empty>
            <x-icon name="file-x" class="w-12 h-12 mx-auto text-base-content/30 mb-2" />
            <p class="text-base-content/60">No versions available</p>
        </x-slot:empty>
    </x-table>

    @if($versions->hasPages())
        <div class="mt-6">
            {{ $versions->links('vendor.livewire.tailwind', ['scrollTo' => false]) }}
        </div>
    @endif
</x-card>

