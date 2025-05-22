<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-medium">Version Tags</h2>
        <button wire:click="createVersionTag" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
            @svg('lucide-plus', 'w-5 h-5 inline-block mr-1')
            Add Version Tag
        </button>
    </div>

    @if($versionTagId !== null || !$isEditingVersionTag)
        <div class="bg-zinc-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-medium mb-4">{{ $isEditingVersionTag ? 'Edit Version Tag' : 'Create Version Tag' }}</h3>
            <form wire:submit="saveVersionTag" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="versionTagName" class="block text-sm font-medium text-gray-400 mb-1">Name</label>
                        <input type="text" id="versionTagName" wire:model="versionTagName" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        @error('versionTagName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="versionTagIcon" class="block text-sm font-medium text-gray-400 mb-1">Icon</label>
                        <input type="text" id="versionTagIcon" wire:model="versionTagIcon" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        <div class="mt-1 text-xs text-gray-400">
                            Must start with "lucide-". <a href="https://lucide.dev/" target="_blank" class="text-indigo-400 hover:text-indigo-300">Browse icons at lucide.dev</a>
                        </div>
                        @error('versionTagIcon') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="versionTagGroupId" class="block text-sm font-medium text-gray-400 mb-1">Tag Group</label>
                        <select id="versionTagGroupId" wire:model="versionTagGroupId" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                            <option value="">None</option>
                            @foreach($versionTagGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Project Types</label>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                        @foreach($projectTypes as $type)
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model="versionTagProjectTypes" value="{{ $type->id }}" class="bg-zinc-700 border-zinc-600 text-indigo-600 rounded">
                                <span class="ml-2 text-sm text-gray-300 flex items-center">
                                    @svg($type->icon, 'w-5 h-5 mr-1')
                                    <span>{{ $type->display_name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" wire:click="resetVersionTagForm" class="bg-zinc-600 hover:bg-zinc-700 text-white px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        {{ $isEditingVersionTag ? 'Update Version Tag' : 'Create Version Tag' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="bg-zinc-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-700">
            <thead class="bg-zinc-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Icon</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Group</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Project Types</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-zinc-800 divide-y divide-zinc-700">
                @foreach($versionTags as $tag)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ $tag->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ $tag->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            @svg($tag->icon, 'w-5 h-5 inline-block mr-1')
                            {{ $tag->icon }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            {{ $tag->tagGroup ? $tag->tagGroup->name : 'None' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            <ul class="flex flex-col flex-wrap">
                                @foreach($tag->projectTypes as $type)
                                    <li class="flex items-center mr-2 mb-1">
                                        @svg($type->icon, 'w-5 h-5 mr-1')
                                        {{ $type->display_name }}
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="editVersionTag({{ $tag->id }})" class="text-indigo-400 hover:text-indigo-300 mr-3">
                                @svg('lucide-edit', 'w-5 h-5')
                            </button>
                            <button wire:click="confirmDeletion({{ $tag->id }})" class="text-red-400 hover:text-red-300">
                                @svg('lucide-trash-2', 'w-5 h-5')
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($confirmingDeletion)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-zinc-800 rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-medium mb-4">Confirm Deletion</h3>
            <p class="text-gray-300 mb-6">Are you sure you want to delete this version tag? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="$set('confirmingDeletion', false)" class="bg-zinc-600 hover:bg-zinc-700 text-white px-4 py-2 rounded-md">
                    Cancel
                </button>
                <button wire:click="deleteItem" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                    Delete
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
