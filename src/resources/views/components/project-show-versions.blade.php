@props(['project', 'versions', 'sortBy', 'versionTagGroups' => null])

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
        <div class="flex items-center gap-2" x-data="{ showVersionFilterModal: false }">
            @if ($versionTagGroups && $versionTagGroups->count() > 0)
                <x-button label="Filter" icon="filter" class="btn-sm btn-primary" @click="showVersionFilterModal = true"
                    responsive />

                <x-mary-modal x-show="showVersionFilterModal" title="Filter Versions" class="backdrop-blur-sm">
                    <x-project-filters
                        :version-tag-groups="$versionTagGroups"
                        selected-version-tags-model="selectedVersionTags"
                        release-date-period-model="releaseDatePeriod"
                        :release-date-period="$releaseDatePeriod"
                        release-date-start-model="releaseDateStart"
                        release-date-end-model="releaseDateEnd"
                    />
                    <x-slot:actions>
                        <x-button label="Close" @click="showVersionFilterModal = false" />
                    </x-slot:actions>
                </x-mary-modal>
            @endif

            <label for="versionsPerPage" class="text-sm">Show:</label>
            <x-select wire:model.live="versionsPerPage" :options="[
                ['id' => 10, 'name' => '10'],
                ['id' => 25, 'name' => '25'],
                ['id' => 50, 'name' => '50'],
                ['id' => 100, 'name' => '100'],
            ]" option-key="id" option-value="id"
                option-label="name" class="select-sm" />
        </div>
    </x-slot:title>

    <x-table :headers="$headers" :rows="$versions" :sort-by="$sortBy" class="table-sm">
        @scope('cell_version_display', $version)
            <div class="flex flex-col gap-1">
                <span class="font-semibold truncate">{{ $version->name }}</span>
                <div class="flex items-center gap-2">
                    <span class="text-sm">{{ $version->version }}</span>
                    <x-badge :value="$version->display_name" class="badge-sm badge-soft badge-{{ $version->bg_color_class }}" />
                </div>
            </div>
        @endscope

        @scope('cell_tags', $version)
            <div class="flex flex-wrap gap-1">
                @foreach ($version->mainTags as $tag)
                    <div class="version-tag flex flex-col items-center gap-1">
                        <div class="badge badge-sm badge-primary badge-soft gap-1">
                            <x-icon :name="$tag->icon" class="w-3 h-3" />
                            {{ $tag->name }}
                        </div>
                        @if ($version->tags->where('parent_id', $tag->id)->count() > 0)
                            <div class="version-subtags flex gap-1 justify-evenly">
                                @foreach ($version->tags->where('parent_id', $tag->id) as $subTag)
                                    <div class="badge badge-sm badge-soft gap-1">
                                        <x-icon :name="$subTag->icon" class="w-3 h-3" />
                                        {{ $subTag->name }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
                {{-- Tags without parent (shouldn't happen but just in case) --}}
                @foreach ($version->tags->whereNull('parent_id') as $tag)
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
                        icon="settings" class="btn-ghost btn-sm" />
                @endcan
                <x-button
                    link="{{ route('project.version.show', ['projectType' => $version->project->projectType, 'project' => $version->project, 'version_key' => $version]) }}"
                    icon="download" class="btn-primary btn-sm" />
            </div>
        @endscope

        <x-slot:empty>
            <x-icon name="file-x" class="w-12 h-12 mx-auto mb-2" />
            <p class="text-base-content/60">No versions available</p>
        </x-slot:empty>
    </x-table>

    @if ($versions->hasPages())
        <div class="mt-6">
            {{ $versions->links('vendor.livewire.tailwind', ['scrollTo' => false]) }}
        </div>
    @endif
</x-card>
