<div class="w-full lg:w-10/12 m-auto py-6">
    <!-- Back Button -->
    <div class="mb-6">
        @if($isEditing)
            <x-button link="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}" icon="lucide-arrow-left" label="Back to Project" />
        @else
            <x-button link="{{ route('project-search', ['projectType' => $projectType]) }}" icon="lucide-arrow-left" label="Back to Projects" />
        @endif
    </div>

    <x-card>
        <h1 class="text-2xl font-bold mb-6">
            @if($isEditing)
                Edit Project: {{ $project->pretty_name }}
            @else
                Create New {{ $projectType->display_name }}
            @endif
        </h1>

        <form wire:submit="save" class="space-y-6">
            <!-- Name -->
            <x-input label="Name" wire:model.live.debounce.500ms="name" placeholder="Project name" required />

            <!-- Slug -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium">URL Slug</label>
                    <x-button type="button" wire:click="generateSlug" label="Generate from Name" class="btn-sm" />
                </div>
                <x-input wire:model.live.debounce.500ms="slug" placeholder="project-slug" prefix="{{ route('dummy.project.show', ['projectType' => $projectType, 'project' => null]) }}/" required />
                @if($isEditing)
                    <p class="text-warning text-xs mt-1">Warning: Changing the slug will change all URLs.</p>
                @else
                    <p class="text-gray-400 text-xs mt-1">The URL slug will be used in links to your project.</p>
                @endif
            </div>

            <!-- Summary -->
            <x-textarea label="Summary" wire:model="summary" placeholder="Brief project description" rows="2" required />

            <!-- Description (Markdown) -->
            <div x-data="{ mode: 'code' }">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium">Description (Markdown)</label>
                    <div class="join">
                        <button type="button" @click="mode = 'code'" :class="{ 'join-item btn-active': mode === 'code' }" class="join-item btn btn-sm">
                            <x-icon name="lucide-code" class="w-4 h-4" /> Code
                        </button>
                        <button type="button" @click="mode = 'preview'" :class="{ 'join-item btn-active': mode === 'preview' }" class="join-item btn btn-sm">
                            <x-icon name="lucide-eye" class="w-4 h-4" /> Preview
                        </button>
                    </div>
                </div>
                <div x-show="mode === 'code'">
                    <x-code
                        wire:model.live="description" 
                        height="300px"
                        language="markdown"
                        hint="Markdown"
                        wrap=1
                        required />
                </div>
                <div x-show="mode === 'preview'" x-cloak class="bg-base-200 rounded-lg p-4 min-h-[242px]">
                    <x-markdown class="prose prose-invert max-w-none" flavor="github">{{ $description }}</x-markdown>
                </div>
            </div>

            <!-- Project Logo -->
            <x-file
                wire:model="logo"
                label="Project Logo"
                accept="image/*"
                crop-after-change
                hint="Recommended: Square image, 512x512px"
                :is-image=1
                :image-url="$isEditing && $project->logo_path ? Storage::url($project->logo_path) : null"
                :placeholder-url="asset('images/placeholder.png')"
                remove-method="removeLogo"
            >
            </x-file>

            <!-- Tags -->
            <div>
                <label class="text-sm font-medium mb-2 block">Tags</label>
                @foreach($tagGroups as $tagGroup)
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">{{ $tagGroup->name }}</h3>
                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($tagGroup->tags as $tag)
                                <x-checkbox
                                    wire:model.live="selectedTags"
                                    value="{{ $tag->id }}"
                                    class="text-sm"
                                >
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
            </div>

            <!-- Links -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <x-input label="Website URL" wire:model="website" type="url" icon="globe" placeholder="https://example.com" />
                <x-input label="Issues URL" wire:model="issues" type="url" icon="bug" placeholder="https://github.com/user/repo/issues" />
                <x-input label="Source Code URL" wire:model="source" type="url" icon="code" placeholder="https://github.com/user/repo" />
            </div>

            <!-- Status -->
            <div>
                <x-toggle label="{{ $status === 'active' ? 'Active' : 'Inactive' }}" hint="Set a project as inactive to inform users that it is no longer maintained." wire:click="toggleStatus" :checked="$status === 'active'" />
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <x-button type="submit" label="{{ $isEditing ? 'Save Changes' : 'Create Project' }}" class="btn-primary" />
            </div>
        </form>

        @if($isEditing)
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
                        @foreach($memberships as $membership)
                            <tr>
                                <td>{{ $membership->user->name }}</td>
                                <td><span class="badge">{{ ucfirst($membership->role) }}{{ $membership->primary ? ' (Primary)' : '' }}</span></td>
                                <td><span class="badge">{{ ucfirst($membership->status) }}</span></td>
                                <td>
                                    <div class="flex gap-2">
                                        @can('removeMember', $project)
                                            @if(!$membership->primary && $membership->status === 'active')
                                                <x-button wire:click="setPrimaryMember({{ $membership->id }})" wire:confirm="Set as primary owner?" label="Make Primary" class="btn-sm btn-info" />
                                            @endif
                                        @endcan
                                        @if($membership->user_id === auth()->id() && !$membership->primary)
                                            <x-button wire:click="removeMember({{ $membership->id }})" wire:confirm="Leave project?" label="Leave" class="btn-sm btn-error" />
                                        @elseif($membership->user_id !== auth()->id() && (!$membership->primary || $project->memberships()->where('primary', true)->count() > 1))
                                            @can('removeMember', $project)
                                                <x-button wire:click="removeMember({{ $membership->id }})" wire:confirm="Remove member?" label="Remove" class="btn-sm btn-error" />
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="divider my-8"></div>

            <!-- Add New Member -->
            @can('addMember', $project)
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2">Add New Member</h3>
                    <p class="text-sm text-gray-400 mb-4">Only one member can be the primary owner. Non-primary members can leave anytime.</p>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <x-input label="Username" wire:model="newMemberName" placeholder="Enter username" class="col-span-2" />
                        <x-select label="Role" wire:model="newMemberRole" :options="collect($roles)->map(fn($role) => ['id' => $role, 'name' => ucfirst($role)])" />
                    </div>
                    <div class="mt-4 flex justify-end">
                        <x-button wire:click="addMember" label="Send Invitation" class="btn-primary" />
                    </div>
                </div>
            @endcan

            <!-- Project Deletion Section -->
            @can('delete', $project)
            <div class="divider my-8"></div>
                <h2 class="text-xl font-bold mb-4 text-error">Delete Project</h2>
                <x-alert title="Warning" icon="lucide-alert-triangle" description="Deleting a project is permanent. It will be soft-deleted and visible to members for 14 days." class="alert-error mb-4" />
                <div class="space-y-4">
                    <x-input label="Confirm by typing project name" wire:model="deleteConfirmation" placeholder="{{ $project->name }}" />
                    <div class="flex justify-end">
                        <x-button wire:click="deleteProject" wire:confirm="Delete this project?" label="Delete Project" class="btn-error" />
                    </div>
                </div>
            @endcan
        @endif
    </x-card>
</div>

