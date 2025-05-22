<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold">User Management</h1>
        </div>

        <!-- Search and Filters -->
        <div class="bg-zinc-800 rounded-lg shadow p-4 mb-6">
            <div class="flex flex-col lg:flex-row gap-4">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-gray-400 mb-1">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            @svg('lucide-search', 'w-5 h-5 text-gray-400')
                        </div>
                        <input type="text" id="search" wire:model.live="search" class="bg-zinc-700 border border-zinc-600 text-white block w-full pl-10 pr-3 py-2 rounded-md" placeholder="Search by name or email">
                    </div>
                </div>
                <div class="w-full lg:w-32">
                    <label for="perPage" class="block text-sm font-medium text-gray-400 mb-1">Per Page</label>
                    <select id="perPage" wire:model.live="perPage" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- User Form -->
        @if($userId !== null || !$isEditing)
        <div class="bg-zinc-800 rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-medium mb-4">{{ $isEditing ? 'Edit User' : 'Create User' }}</h2>
            <form wire:submit="saveUser" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-400 mb-1">Name</label>
                        <input type="text" id="name" wire:model="name" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                        <input type="email" id="email" wire:model="email" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-400 mb-1">
                            Password {{ $isEditing ? '(leave blank to keep current)' : '' }}
                        </label>
                        <input type="password" id="password" wire:model="password" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-400 mb-1">Role</label>
                        <select id="role" wire:model="role" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                        @error('role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" wire:click="resetForm" class="bg-zinc-600 hover:bg-zinc-700 text-white px-4 py-2 rounded-md">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                        {{ $isEditing ? 'Update User' : 'Create User' }}
                    </button>
                </div>
            </form>
        </div>
        @endif

        <!-- Users Table -->
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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('email')">
                            Email
                            @if ($sortField === 'email')
                                @if ($sortDirection === 'asc')
                                    @svg('lucide-chevron-up', 'w-4 h-4 inline-block ml-1')
                                @else
                                    @svg('lucide-chevron-down', 'w-4 h-4 inline-block ml-1')
                                @endif
                            @endif
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer" wire:click="sortBy('role')">
                            Role
                            @if ($sortField === 'role')
                                @if ($sortDirection === 'asc')
                                    @svg('lucide-chevron-up', 'w-4 h-4 inline-block ml-1')
                                @else
                                    @svg('lucide-chevron-down', 'w-4 h-4 inline-block ml-1')
                                @endif
                            @endif
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
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ $user->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                {{ $user->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ $user->email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="editUser('{{ $user->name }}')" class="text-indigo-400 hover:text-indigo-300 mr-3">
                                    @svg('lucide-edit', 'w-5 h-5')
                                </button>
                                <button wire:click="confirmUserDeletion('{{ $user->name }}')" class="text-red-400 hover:text-red-300">
                                    @svg('lucide-trash-2', 'w-5 h-5')
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 bg-zinc-800 border-t border-zinc-700">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($confirmingUserDeletion)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-zinc-800 rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-medium mb-4">Confirm Deletion</h3>
            <p class="text-gray-300 mb-6">Are you sure you want to delete this user? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="$set('confirmingUserDeletion', false)" class="bg-zinc-600 hover:bg-zinc-700 text-white px-4 py-2 rounded-md">
                    Cancel
                </button>
                <button wire:click="deleteUser" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                    Delete User
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
