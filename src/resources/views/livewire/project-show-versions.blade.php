<x-card>
    <x-slot:title class="flex justify-between items-center">
        <span>All Versions</span>
        <div class="flex items-center gap-2">
            <label for="perPage" class="text-sm">Show:</label>
            <x-select
                wire:model.live="perPage"
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

    @if($versions->count() > 0)
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Version</th>
                        <th class="hidden lg:table-cell">Tags</th>
                        <th class="hidden lg:table-cell">Release Date</th>
                        <th class="hidden lg:table-cell">Downloads</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($versions as $version)
                        <tr class="hover">
                            <td>
                                <div class="flex flex-col gap-1">
                                    <span class="font-semibold truncate">{{ $version->name }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm">{{ $version->version }}</span>
                                        <x-badge :value="$version->display_name" class="badge-sm badge-soft" />
                                    </div>
                                </div>
                            </td>
                            <td class="hidden lg:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($version->tags as $tag)
                                        <x-badge :value="$tag->name" class="badge-sm gap-1 badge-soft">
                                            <x-icon :name="$tag->icon" class="w-3 h-3" />
                                        </x-badge>
                                    @endforeach
                                </div>
                            </td>
                            <td class="hidden lg:table-cell text-sm">
                                {{ $version->release_date->format('M d, Y') }}
                            </td>
                            <td class="hidden lg:table-cell text-sm">
                                {{ number_format($version->downloads) }}
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @can('editVersion', $project)
                                        <x-button
                                            link="{{ route('project.version.edit', ['projectType' => $project->projectType, 'project' => $project, 'version_key' => $version]) }}"
                                            icon="settings"
                                            class="btn-ghost btn-sm"
                                        />
                                    @endcan
                                    <x-button
                                        link="{{ route('project.version.show', ['projectType' => $project->projectType, 'project' => $project, 'version_key' => $version]) }}"
                                        icon="download"
                                        class="btn-primary btn-sm"
                                    />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $versions->links('vendor.livewire.tailwind') }}
        </div>
    @else
        <div class="text-center py-12">
            <x-icon name="file-x" class="w-12 h-12 mx-auto text-base-content/30 mb-2" />
            <p class="text-base-content/60">No versions available</p>
        </div>
    @endif
</x-card>

