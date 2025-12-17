<div class="w-full lg:w-10/12 m-auto py-6">
    <x-card>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">User Management</h1>
            <x-button label="Create User" icon="lucide-user-plus" wire:click="createUser" class="btn-primary" />
        </div>

        {{-- Search --}}
        <div class="mb-4">
            <x-input placeholder="Search users by name or email..." wire:model.live.debounce.300ms="search"
                icon="lucide-search" clearable />
        </div>

        {{-- Users Table --}}
        <x-table :headers="[
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'role', 'label' => 'Role'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'created_at', 'label' => 'Created'],
            ['key' => 'actions', 'label' => 'Actions'],
        ]" :rows="$users" :sort-by="$sortBy" with-pagination>
            @scope('cell_name', $user)
                <div class="flex items-center gap-2">
                    <x-avatar placeholder="{{ strtoupper(substr($user->name, 0, 1)) }}"
                        placeholder-text-class="text-sm font-bold" placeholder-bg-class="bg-primary text-primary-content"
                        class="!w-8" image="{{ $user->getAvatarUrl() }}" />
                    <span class="font-medium">{{ $user->name }}</span>
                </div>
            @endscope

            @scope('cell_role', $user)
                <x-badge :value="ucfirst($user->role)" class="{{ $user->role === 'admin' ? 'badge-primary' : 'badge-ghost' }}" />
            @endscope

            @scope('cell_status', $user)
                @if ($user->isDeactivated())
                    <x-badge value="Deactivated" class="badge-error" />
                @else
                    <x-badge value="Active" class="badge-success" />
                @endif
            @endscope

            @scope('cell_created_at', $user)
                {{ $user->created_at->diffForHumans() }}
            @endscope

            @scope('cell_actions', $user)
                <div class="flex gap-2">
                    <x-button icon="lucide-pencil" wire:click="editUser({{ $user->id }})" class="btn-sm btn-ghost"
                        tooltip="Edit user" />
                    @if ($user->isDeactivated())
                        <x-button icon="lucide-user-check" wire:click="reactivateUser({{ $user->id }})"
                            class="btn-sm btn-ghost text-success" tooltip="Reactivate user"
                            wire:confirm="Are you sure you want to reactivate this user?" />
                    @else
                        <x-button icon="lucide-user-x" wire:click="deactivateUser({{ $user->id }})"
                            class="btn-sm btn-ghost text-warning" tooltip="Deactivate user"
                            wire:confirm="Are you sure you want to deactivate this user? They will be logged out immediately." />
                    @endif
                    <x-button icon="lucide-trash-2" wire:click="confirmUserDeletion({{ $user->id }})"
                        class="btn-sm btn-ghost text-error" tooltip="Delete user" />
                </div>
            @endscope
        </x-table>
    </x-card>

    {{-- Create/Edit Modal --}}
    <x-modal wire:model="showModal" title="{{ $isEditing ? 'Edit User' : 'Create User' }}"
        subtitle="{{ $isEditing ? 'Update user information' : 'Add a new user to the system' }}">
        <x-form wire:submit="saveUser">
            <x-input label="Name" wire:model="name" required />
            <x-input label="Email" type="email" wire:model="email" required />
            <x-password label="Password" wire:model="password"
                hint="{{ $isEditing ? 'Leave blank to keep current password' : 'Minimum 8 characters' }}"
                :required="!$isEditing" />
            <x-select label="Role" wire:model="role" :options="[['id' => 'user', 'name' => 'User'], ['id' => 'admin', 'name' => 'Admin']]" required />

            <x-slot:actions>
                <x-button label="Cancel" wire:click="$set('showModal', false)" />
                <x-button label="{{ $isEditing ? 'Update' : 'Create' }}" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- Delete Confirmation Modal --}}
    <x-modal wire:model="confirmingUserDeletion" title="Delete User">
        @if ($userToDelete)
            <p>Are you sure you want to delete <strong>{{ $userToDelete->name }}</strong>?</p>
            <p class="text-sm text-gray-400 mt-2">This action cannot be undone.</p>
        @endif

        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('confirmingUserDeletion', false)" />
            <x-button label="Delete" wire:click="deleteUser" class="btn-error" />
        </x-slot:actions>
    </x-modal>
</div>
