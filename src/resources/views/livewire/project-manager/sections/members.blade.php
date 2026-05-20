<x-card class="space-y-6">
    {{-- Membership Management Section --}}
    <div>
        <h2 class="text-xl font-bold mb-4">Project Members</h2>

        {{-- Current Members Table --}}
        <div class="mb-6 overflow-x-auto">
            <h3 class="text-lg font-semibold mb-2">Current Members</h3>
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($memberships as $membership)
                        @php
                            $isSelf = $membership->user_id === auth()->id();
                        @endphp
                        <tr>
                            <td>{{ $membership->user ? $membership->user->name : 'Unknown User' }}</td>
                            <td>
                                @php
                                    $role_label = ucfirst($membership->role) . ($membership->primary ? ' (Primary)' : '');
                                @endphp
                                <x-badge :value="$role_label"
                                    class="badge-soft {{ $membership->primary ? 'badge-primary' : '' }}" />
                            </td>
                            <td>
                                @php
                                    $status_color = '';
                                    switch ($membership->status) {
                                        case 'pending':
                                            $status_color = 'badge-warning';
                                            break;
                                        case 'rejected':
                                            $status_color = 'badge-error';
                                            break;
                                        default:
                                            $status_color = 'badge-success';
                                            break;
                                    }
                                @endphp
                                <x-badge :value="ucfirst($membership->status)" class="badge-soft {{ $status_color }}" />
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    @can('setPrimary', $membership)
                                        <x-button wire:click="setPrimaryMember({{ $membership->id }})"
                                            wire:confirm="Set as primary owner?" label="Make Primary"
                                            class="btn-sm btn-info" />
                                    @endcan

                                    @can('delete', $membership)
                                        <x-button wire:click="removeMember({{ $membership->id }})"
                                            wire:confirm="{{ $isSelf ? 'Leave project?' : 'Remove member?' }}"
                                            label="{{ $isSelf ? 'Leave' : 'Remove' }}" class="btn-sm btn-error" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Add New Member --}}
        @can('addMember', $project)
            <div class="divider my-8"></div>
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">Add New Member</h3>
                <p class="text-sm text-gray-400 mb-4">Only one member can be the primary owner. Non-primary members can leave anytime.</p>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <x-input label="Username" wire:model="newMemberName" placeholder="Enter username"
                        class="col-span-2" />
                    <x-select label="Role" wire:model="newMemberRole" :options="collect($roles)->map(fn($role) => ['id' => $role, 'name' => ucfirst($role)])" />
                </div>
                <div class="mt-4 flex justify-end">
                    <x-button spinner wire:click="addMember" label="Send Invitation" class="btn-primary" />
                </div>
            </div>
        @endcan
    </div>
</x-card>
