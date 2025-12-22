<div class="w-full lg:w-10/12 m-auto py-6">
    <!-- Back Button -->
    <div class="mb-6">
        @if ($isEditing)
            <x-button link="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}"
                icon="lucide-arrow-left" label="Back to Project" />
        @else
            <x-button link="{{ route('project-search', ['projectType' => $projectType]) }}" icon="lucide-arrow-left"
                label="Back to Projects" />
        @endif
    </div>

    <x-card>
        <h1 class="text-2xl font-bold mb-6">
            @if ($isEditing)
                Edit Project: {{ $project->pretty_name }}
            @else
                Create New {{ $projectType->display_name }}
            @endif
        </h1>

        {{-- Approval Status Banner (for editing) --}}
        @if ($isEditing)
            @if ($approvalStatus === \App\Enums\ApprovalStatus::PENDING)
                <x-alert icon="lucide-clock" class="alert-warning mb-6">
                    <div>
                        <div class="font-semibold">Project Under Review</div>
                        <p class="text-sm mt-1">Your project is currently pending admin approval. You can make edits,
                            but it won't be visible to the public until approved.</p>
                    </div>
                </x-alert>
            @elseif($approvalStatus === \App\Enums\ApprovalStatus::REJECTED)
                <x-alert icon="lucide-x-circle" class="alert-error mb-6">
                    <div>
                        <div class="font-semibold">Project Rejected</div>
                        <p class="text-sm mt-1">Your project was rejected by an admin. Please review the feedback below,
                            make the necessary changes, and save to resubmit for review.</p>
                        @if ($rejectionReason)
                            <div class="mt-3 p-3 bg-base-300 rounded-lg">
                                <div class="text-xs font-semibold mb-1">Admin Feedback:</div>
                                <div class="text-sm">{{ $rejectionReason }}</div>
                            </div>
                        @endif
                    </div>
                </x-alert>
            @elseif($approvalStatus === \App\Enums\ApprovalStatus::APPROVED)
                <x-alert icon="lucide-check-circle" class="alert-success mb-6">
                    <div class="font-semibold">Project Approved ✓</div>
                    <p class="text-sm mt-1">Your project is approved and visible to all users.</p>
                </x-alert>
            @endif
        @endif

        <form wire:submit="save" class="space-y-6">
            <!-- Name -->
            @if ($isEditing)
                <x-input label="Name" wire:model="name" placeholder="Project name" required />
            @else
                <x-input label="Name" wire:model.live.debounce.500ms="name" placeholder="Project name" required />
            @endif

            <!-- Slug -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium">
                        URL Slug
                        <span class="text-error">*</span>
                        <span wire:loading wire:target="name, slug" class="loading loading-spinner w-4 h-4"></span>
                    </label>
                    <x-button spinner type="button" wire:click="generateSlug" label="Generate from Name"
                        class="btn-sm" />
                </div>
                <x-input wire:model.live.debounce.500ms="slug" placeholder="project-slug"
                    prefix="{{ route('dummy.project.show', ['projectType' => $projectType, 'project' => null]) }}/"
                    required />
                @if ($isEditing)
                    <p class="text-warning text-xs mt-1">Warning: Changing the slug will change all URLs.</p>
                @else
                    <p class="text-gray-400 text-xs mt-1">The URL slug will be used in links to your project.</p>
                @endif
            </div>

            <!-- Summary -->
            <x-textarea label="Summary" wire:model="summary" placeholder="Brief project description" rows="2"
                maxlength="125" hint="Max 125 characters" required />

            <!-- Description (Markdown) -->
            <div x-data="{ mode: 'code' }">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium">Description (Markdown)</label>
                    <div class="join">
                        <button type="button" @click="mode = 'code'"
                            :class="{ 'join-item btn-active': mode === 'code' }" class="join-item btn btn-sm">
                            <x-icon name="lucide-code" class="w-4 h-4" /> Code
                        </button>
                        <button type="button" @click="$wire.refreshMarkdown().then(() => mode = 'preview')"
                            :class="{ 'join-item btn-active': mode === 'preview' }" class="join-item btn btn-sm">
                            <x-icon name="lucide-eye" class="w-4 h-4" /> Preview
                        </button>
                    </div>
                </div>
                <div wire:loading.remove wire:target="refreshMarkdown" x-show="mode === 'code'">
                    <x-code wire:model="description" height="300px" language="markdown" hint="Markdown" wrap=1
                        required />
                </div>
                <div wire:loading.flex wire:target="refreshMarkdown"
                    class="bg-base-200 rounded-lg p-4 min-h-[242px] w-full flex items-center justify-center">
                    <span class="loading loading-spinner w-10 h-10"></span>
                </div>
                <div x-show="mode === 'preview'" x-cloak class="bg-base-200 rounded-lg p-4 min-h-[242px]">
                    <x-markdown class="prose prose-invert max-w-none" flavor="github">{{ $description }}</x-markdown>
                </div>
            </div>

            <!-- Project Logo -->
            <x-file wire:model="logo" label="Project Logo" accept="image/*" crop-after-change
                hint="Recommended: Square image, 512x512px" :is-image=1 :image-url="$isEditing && $project->logo_path ? Storage::url($project->logo_path) : null" :placeholder-url="asset('images/placeholder.png')"
                remove-method="removeLogo">
            </x-file>

            <!-- Tags -->
            <div>
                <label class="text-sm font-medium mb-2 block">Tags</label>
                @foreach ($tagGroups as $tagGroup)
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">{{ $tagGroup->name }}</h3>
                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach ($tagGroup->tags as $tag)
                                <x-checkbox wire:model="selectedTags" value="{{ $tag->id }}" class="text-sm">
                                    <x-slot:label class="flex items-center gap-2">
                                        <div class="flex items-center gap-2">
                                            <x-icon :name="$tag->icon" class="w-4 h-4" />
                                            {{ $tag->name }}
                                        </div>
                                    </x-slot:label>
                                </x-checkbox>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @error('selectedTags')
                    <span class="text-error text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Links -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <x-input label="Website URL" wire:model="website" type="url" icon="globe"
                    placeholder="https://example.com" />
                <x-input label="Issues URL" wire:model="issues" type="url" icon="bug"
                    placeholder="https://github.com/user/repo/issues" />
                <x-input label="Source Code URL" wire:model="source" type="url" icon="code"
                    placeholder="https://github.com/user/repo" />
            </div>

            <!-- Status -->
            <div>
                <x-custom-toggle hint="Set a project as inactive to inform users that it is no longer maintained."
                    wire:model="status" on-value="active" off-value="inactive" on-label="Active"
                    off-label="Inactive" />
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <x-button spinner type="submit" label="{{ $isEditing ? 'Save Changes' : 'Create Project' }}"
                    class="btn-primary" />
            </div>
        </form>

        @if ($isEditing)
            <!-- Membership Management Section -->
            <div class="divider my-8"></div>
            <h2 class="text-xl font-bold mb-4">Project Members</h2>

            <!-- Current Members Table -->
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
                                <td>{{ $membership->user->name }}</td>
                                <td>
                                    @php
                                        $role_label =
                                            ucfirst($membership->role) . ($membership->primary ? ' (Primary)' : '');
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

            <!-- Add New Member -->
            @can('addMember', $project)
                <div class="divider my-8"></div>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2">Add New Member</h3>
                    <p class="text-sm text-gray-400 mb-4">Only one member can be the primary owner. Non-primary members can
                        leave anytime.</p>
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

            <!-- Project Deletion Section -->
            @can('delete', $project)
                <div class="divider my-8"></div>
                <h2 class="text-xl font-bold mb-4 text-error">Delete Project</h2>
                <x-alert title="Warning" icon="lucide-alert-triangle"
                    description="Deleting a project is permanent. It will be soft-deleted and visible to members for 14 days."
                    class="alert-error mb-4" />
                <div class="space-y-4">
                    <x-input label="Confirm by typing project name" wire:model="deleteConfirmation"
                        placeholder="{{ $project->name }}" />
                    <div class="flex justify-end">
                        <x-button spinner wire:click="deleteProject" wire:confirm="Delete this project?"
                            label="Delete Project" class="btn-error" />
                    </div>
                </div>
            @endcan
        @endif
    </x-card>
</div>
