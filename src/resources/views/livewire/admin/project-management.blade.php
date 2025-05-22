<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold mb-6">Project Management</h1>

        <!-- Search and Filters -->
        <div class="bg-zinc-800 rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-400 mb-1">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            @svg('lucide-search', 'w-5 h-5 text-gray-400')
                        </div>
                        <input type="text" id="search" wire:model.live="search" class="bg-zinc-700 border border-zinc-600 text-white block w-full pl-10 pr-3 py-2 rounded-md" placeholder="Search by name or slug">
                    </div>
                </div>
                <div>
                    <label for="filterType" class="block text-sm font-medium text-gray-400 mb-1">Project Type</label>
                    <select id="filterType" wire:model.live="filterType" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        <option value="">All Types</option>
                        @foreach($projectTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filterStatus" class="block text-sm font-medium text-gray-400 mb-1">Status</label>
                    <select id="filterStatus" wire:model.live="filterStatus" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="deleted">Deleted</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-between mt-4">
                <div>
                    <label for="perPage" class="block text-sm font-medium text-gray-400 mb-1">Per Page</label>
                    <select id="perPage" wire:model.live="perPage" class="bg-zinc-700 border border-zinc-600 text-white px-3 py-1 rounded-md">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="bg-zinc-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-zinc-700">
                <thead class="bg-zinc-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('id')">
                            ID
                            @if ($sortField === 'id')
                                @if ($sortDirection === 'asc')
                                    @svg('lucide-chevron-up', 'w-4 h-4 inline-block ml-1')
                                @else
                                    @svg('lucide-chevron-down', 'w-4 h-4 inline-block ml-1')
                                @endif
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('name')">
                            Name
                            @if ($sortField === 'name')
                                @if ($sortDirection === 'asc')
                                    @svg('lucide-chevron-up', 'w-4 h-4 inline-block ml-1')
                                @else
                                    @svg('lucide-chevron-down', 'w-4 h-4 inline-block ml-1')
                                @endif
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Type
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('status')">
                            Status
                            @if ($sortField === 'status')
                                @if ($sortDirection === 'asc')
                                    @svg('lucide-chevron-up', 'w-4 h-4 inline-block ml-1')
                                @else
                                    @svg('lucide-chevron-down', 'w-4 h-4 inline-block ml-1')
                                @endif
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Size
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('created_at')">
                            Created
                            @if ($sortField === 'created_at')
                                @if ($sortDirection === 'asc')
                                    @svg('lucide-chevron-up', 'w-4 h-4 inline-block ml-1')
                                @else
                                    @svg('lucide-chevron-down', 'w-4 h-4 inline-block ml-1')
                                @endif
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-zinc-800 divide-y divide-zinc-700">
                    @foreach ($projects as $project)
                        <tr class="{{ $project->trashed() ? 'bg-zinc-900 bg-opacity-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ $project->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                {{ $project->name }}
                                <div class="text-xs text-gray-400">{{ $project->slug }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ $project->projectType->display_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                @if($project->trashed())
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Deleted
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $project->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($project->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <div class="flex items-center">
                                    @svg('lucide-hard-drive', 'w-4 h-4 mr-1 text-gray-400')
                                    {{ $project->formatted_size }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ $project->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    @if($project->trashed())
                                        <button wire:click="restoreProject({{ $project->id }})" class="text-green-400 hover:text-green-300">
                                            @svg('lucide-refresh-cw', 'w-5 h-5')
                                        </button>
                                    @else
                                        <button wire:click="showProject({{ $project->id }})" class="text-indigo-400 hover:text-indigo-300">
                                            @svg('lucide-eye', 'w-5 h-5')
                                        </button>
                                        <button wire:click="editProject({{ $project->id }})" class="text-indigo-400 hover:text-indigo-300">
                                            @svg('lucide-edit', 'w-5 h-5')
                                        </button>
                                        <button wire:click="confirmProjectDeletion({{ $project->id }})" class="text-red-400 hover:text-red-300">
                                            @svg('lucide-trash-2', 'w-5 h-5')
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-zinc-800 border-t border-zinc-700">
                {{ $projects->links() }}
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($confirmingProjectDeletion)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-zinc-800 rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-medium mb-4">Confirm Deletion</h3>
            <p class="text-gray-300 mb-6">Are you sure you want to delete this project? This action can be undone from the admin panel.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="$set('confirmingProjectDeletion', false)" class="bg-zinc-600 hover:bg-zinc-700 text-white px-4 py-2 rounded-md">
                    Cancel
                </button>
                <button wire:click="deleteProject" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                    Delete Project
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
