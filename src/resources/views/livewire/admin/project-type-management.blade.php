<div>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-medium">Project Types</h2>
        <button wire:click="createProjectType" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
            @svg('lucide-plus', 'w-5 h-5 inline-block mr-1')
            Add Project Type
        </button>
    </div>

    @if($projectTypeId !== null || !$isEditingProjectType)
        <div class="bg-zinc-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-medium mb-4">{{ $isEditingProjectType ? 'Edit Project Type' : 'Create Project Type' }}</h3>
            <form wire:submit="saveProjectType" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="projectTypeValue" class="block text-sm font-medium text-gray-400 mb-1">Value</label>
                        <input type="text" id="projectTypeValue" wire:model="projectTypeValue" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        @error('projectTypeValue') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="projectTypeDisplayName" class="block text-sm font-medium text-gray-400 mb-1">Display Name</label>
                        <input type="text" id="projectTypeDisplayName" wire:model="projectTypeDisplayName" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        @error('projectTypeDisplayName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="projectTypeIcon" class="block text-sm font-medium text-gray-400 mb-1">Icon</label>
                        <input type="text" id="projectTypeIcon" wire:model="projectTypeIcon" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        <div class="mt-1 text-xs text-gray-400">
                            Must start with "lucide-". <a href="https://lucide.dev/" target="_blank" class="text-indigo-400 hover:text-indigo-300">Browse icons at lucide.dev</a>
                        </div>
                        @error('projectTypeIcon') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" wire:click="resetProjectTypeForm" class="bg-zinc-600 hover:bg-zinc-700 text-white px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        {{ $isEditingProjectType ? 'Update Project Type' : 'Create Project Type' }}
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
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Value</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Display Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Icon</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-zinc-800 divide-y divide-zinc-700">
                @foreach($projectTypes as $type)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ $type->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ $type->value }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{{ $type->display_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            @svg($type->icon, 'w-5 h-5 inline-block mr-1')
                            {{ $type->icon }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="editProjectType({{ $type->id }})" class="text-indigo-400 hover:text-indigo-300 mr-3">
                                @svg('lucide-edit', 'w-5 h-5')
                            </button>
                            <button wire:click="confirmDeletion({{ $type->id }})" class="text-red-400 hover:text-red-300">
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
            <p class="text-gray-300 mb-6">Are you sure you want to delete this project type? This action cannot be undone.</p>
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
