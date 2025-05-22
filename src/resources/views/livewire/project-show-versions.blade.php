<div class="bg-zinc-800 text-gray-300 p-4 mb-6 rounded-md">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">All Versions</h2>
        <div class="flex items-center space-x-2">
            <label for="perPage" class="text-sm">Show:</label>
            <x-forms.select :options="$perPageOptions" :property="'perPage'"></x-forms.select>
        </div>
    </div>

    <div class="versions-list overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-700">
            <thead class="bg-zinc-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-1/2 lg:w-auto">Version</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden lg:table-cell">Tags</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden lg:table-cell">Release Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden lg:table-cell">Downloads</th>
                    <th class="w-1/2 lg:w-auto"></th>
                </tr>
            </thead>
            <tbody class="bg-zinc-800 divide-y divide-zinc-700">
                @foreach ($versions as $version)
                    <tr class="hover:bg-zinc-700">
                        <td class="px-6 py-4 whitespace-nowrap w-1/2 lg:w-auto">
                            <div class="flex flex-col gap-2">
                                <span class="font-semibold truncate">{{ $version->name }}</span>
                                <div>
                                    <span class="font-medium text-sm">{{ $version->version }}</span>
                                    <span class="text-xs {{ $version->bg_color_class }} p-0.5 rounded">{{ $version->display_name }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                            <div class="flex flex-wrap gap-1">
                                @foreach($version->tags as $tag)
                                    <span class="bg-zinc-700 text-xs px-1.5 py-0.5 rounded-full flex items-center">
                                        @svg($tag->icon, 'w-3 h-3 mr-0.5')
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center hidden lg:table-cell">
                            {{ $version->release_date }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center hidden lg:table-cell">
                            {{ $version->downloads }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm w-1/2 lg:w-auto">
                            <div class="flex items-center justify-end gap-2">
                                @can('editVersion', $project)
                                    <a href="{{ route('project.version.edit', ['projectType' => $project->projectType, 'project' => $project, 'version_key' => $version]) }}"
                                       class="text-gray-400 hover:text-gray-300 px-2 py-1 rounded-md inline-flex items-center gap-1">
                                        @svg('lucide-settings', 'w-4 h-4')
                                    </a>
                                @endcan
                                <a href="{{ route('project.version.show', ['projectType' => $project->projectType, 'project' => $project, 'version_key' => $version]) }}"
                                   class="text-gray-400 hover:text-gray-300 px-3 py-1 rounded-md inline-flex items-center gap-1 whitespace-nowrap">
                                    @svg('lucide-download', 'w-4 h-4')
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($versions->count() === 0)
            <div class="text-center py-8 text-gray-400">
                @svg('lucide-file-x', 'w-12 h-12 mx-auto mb-2')
                <p>No versions available</p>
            </div>
        @endif
    </div>

    <div class="mt-6">
        {{ $versions->links('vendor.livewire.tailwind') }}
    </div>
</div>
