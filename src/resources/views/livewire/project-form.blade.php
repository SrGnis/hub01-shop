<div class="w-full lg:w-10/12 m-auto py-6">
    <!-- Back Button -->
    <div class="mb-6 flex justify-between">
        @if($isEditing)
            <a href="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}" class="inline-flex items-center text-indigo-400 hover:text-indigo-300">
                <x-lucide-arrow-left class="w-5 h-5 mr-1" />
                Back to Project
            </a>
        @else
            <a href="{{ route('project-search', ['projectType' => $projectType]) }}" class="inline-flex items-center text-indigo-400 hover:text-indigo-300">
                <x-lucide-arrow-left class="w-5 h-5 mr-1" />
                Back to Projects
            </a>
        @endif
    </div>

    <div class="bg-zinc-800 text-gray-300 p-6 rounded-lg">
        <h1 class="text-2xl font-bold mb-6">
            @if($isEditing)
                Edit Project: {{ $project->pretty_name }}
            @else
                Create New {{ $projectType->display_name }}
            @endif
        </h1>

        <form wire:submit="save" class="space-y-6">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium mb-1">Name</label>
                <input type="text" id="name" wire:model="name" class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Slug -->
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label for="slug" class="block text-sm font-medium">URL Slug</label>
                    <button type="button" wire:click="generateSlug" class="text-xs bg-zinc-700 hover:bg-zinc-600 text-white px-2 py-1 rounded">
                        Generate from Name
                    </button>
                </div>
                <input type="text" id="slug" wire:model="slug" class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @if($isEditing)
                    <p class="text-amber-400 text-xs mt-1">Warning: Changing the slug will change all URLs referring to this project.</p>
                @else
                    <p class="text-gray-400 text-xs mt-1">The URL slug will be used in links to your project.</p>
                @endif
                @error('slug') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Summary -->
            <div>
                <label for="summary" class="block text-sm font-medium mb-1">Summary</label>
                <textarea id="summary" wire:model="summary" rows="2" class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                @error('summary') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Description (Markdown) -->
            <div x-data="{ mode: 'code' }">
                <div class="flex justify-between items-center mb-1">
                    <label for="description" class="text-sm font-medium">Description (Markdown)</label>
                    <div class="flex bg-zinc-700 rounded-md p-0.5">
                        <button type="button"
                                @click="mode = 'code'"
                                :class="{ 'bg-zinc-600': mode === 'code' }"
                                class="px-3 py-1 text-sm rounded-md">
                            <x-lucide-code class="w-4 h-4 inline-block mr-1" />
                            Code
                        </button>
                        <button type="button"
                                @click="mode = 'preview'"
                                :class="{ 'bg-zinc-600': mode === 'preview' }"
                                class="px-3 py-1 text-sm rounded-md">
                            <x-lucide-eye class="w-4 h-4 inline-block mr-1" />
                            Preview
                        </button>
                    </div>
                </div>
                <div>
                    <textarea
                        x-show="mode === 'code'"
                        id="description"
                        wire:model.live="description"
                        rows="10"
                        class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                    ></textarea>
                    <div
                        x-show="mode === 'preview'"
                        x-cloak
                        class="bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 min-h-[242px]"
                    >
                        <x-markdown class="prose prose-invert max-w-none" flawor="github">{{ $description }}</x-markdown>
                    </div>
                </div>
                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Project Logo -->
            <div>
                <label for="logo" class="block text-sm font-medium mb-2">Project Logo</label>
                <div class="flex flex-col lg:flex-row gap-4">
                    <div
                        x-data="{
                            uploading: false,
                            progress: 0,
                            previewUrl: null,
                            updatePreview(event) {
                                const file = event.target.files[0];
                                if (file) {
                                    this.previewUrl = URL.createObjectURL(file);
                                }
                            }
                        }"
                        x-on:livewire-upload-start="uploading = true"
                        x-on:livewire-upload-finish="uploading = false"
                        x-on:livewire-upload-error="uploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress"
                        class="flex flex-col gap-2"
                    >
                        <div class="flex items-center gap-2">
                            <!-- Logo Upload/Preview Area -->
                            @if($isEditing && $project->logo_path)
                                <!-- Existing Logo with Overlay for Replacement -->
                                <div class="relative w-32 h-32">
                                    @if(!$removeLogo)
                                    <label for="logo-upload" class="absolute inset-0 flex flex-col items-center justify-center cursor-pointer z-10 bg-zinc-900 bg-opacity-0 hover:bg-opacity-70 transition-all duration-200 rounded-md overflow-hidden">
                                        <div class="hidden hover:flex flex-col items-center justify-center w-full h-full">
                                            <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4 4m4-4v12" />
                                            </svg>
                                            <p class="text-xs text-center text-white mt-1">
                                                Replace Logo
                                            </p>
                                        </div>
                                        <input
                                            id="logo-upload"
                                            type="file"
                                            wire:model="logo"
                                            @change="updatePreview($event)"
                                            class="hidden"
                                            accept="image/*"
                                        />
                                    </label>
                                    @endif
                                    <img
                                        src="{{ Storage::url($project->logo_path) }}"
                                        alt="Project Logo"
                                        class="w-full h-full object-cover bg-zinc-700 border border-zinc-600 rounded-md {{ $removeLogo ? 'opacity-30' : '' }}"
                                    >
                                </div>

                                <!-- Delete Button -->
                                <button
                                    type="button"
                                    @if($removeLogo)
                                        wire:click="$set('removeLogo', false)"
                                    @else
                                        wire:click="$set('removeLogo', true)"
                                    @endif
                                    class="h-8 px-2 py-1 rounded-md {{ $removeLogo ? 'bg-zinc-600 text-gray-300' : 'bg-red-600 hover:bg-red-700 text-white' }} text-xs flex items-center"
                                >
                                    @if($removeLogo)
                                        <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        Undo
                                    @else
                                        <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    @endif
                                </button>
                            @else
                                <!-- Upload New Logo -->
                                <label for="logo-upload" class="flex flex-col items-center justify-center w-32 h-32 border-2 border-dashed border-zinc-600 rounded-lg cursor-pointer bg-zinc-700 hover:bg-zinc-600 overflow-hidden">
                                    <template x-if="previewUrl">
                                        <img :src="previewUrl" class="w-full h-full object-cover" />
                                    </template>
                                    <template x-if="!previewUrl">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg class="w-8 h-8 mb-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p class="text-xs text-center text-gray-400">
                                                Upload Logo
                                            </p>
                                        </div>
                                    </template>
                                    <input
                                        id="logo-upload"
                                        type="file"
                                        wire:model="logo"
                                        @change="updatePreview($event)"
                                        class="hidden"
                                        accept="image/*"
                                    />
                                </label>
                            @endif
                        </div>

                        <!-- Progress Bar -->
                        <div x-show="uploading" class="w-32">
                            <div class="w-full bg-zinc-700 rounded-full h-1.5">
                                <div class="bg-indigo-600 h-1.5 rounded-full" x-bind:style="'width: ' + progress + '%'"></div>
                            </div>
                        </div>

                        <div class="text-xs text-gray-400">
                            Recommended: Square image, 512x512px
                        </div>

                        @error('logo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Tags -->
            <div>
                <label class="block text-sm font-medium mb-2">Tags</label>
                @foreach($tagGroups as $tagGroup)
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">{{ $tagGroup->name }}</h3>
                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($tagGroup->tags as $tag)
                                <label class="flex items-center space-x-2 p-2 border border-zinc-600 rounded-md hover:bg-zinc-700 cursor-pointer">
                                    <input type="checkbox" wire:model="selectedTags" value="{{ $tag->id }}" class="rounded bg-zinc-700 border-zinc-500 text-indigo-600 focus:ring-indigo-500">
                                    <span class="flex items-center">
                                        @svg($tag->icon, 'w-4 h-4 mr-2')
                                        {{ $tag->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @error('selectedTags') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Links -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Website -->
                <div>
                    <label for="website" class="block text-sm font-medium mb-1">Website URL</label>
                    <input type="url" id="website" wire:model="website" class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('website') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Issues -->
                <div>
                    <label for="issues" class="block text-sm font-medium mb-1">Issues URL</label>
                    <input type="url" id="issues" wire:model="issues" class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('issues') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Source -->
                <div>
                    <label for="source" class="block text-sm font-medium mb-1">Source Code URL</label>
                    <input type="url" id="source" wire:model="source" class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('source') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Status Toggle -->
                <div>
                    <label for="status" class="block text-sm font-medium mb-1">Status</label>
                    <div class="flex items-center space-x-3">
                        <button
                            type="button"
                            wire:click="toggleStatus"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $status === 'active' ? 'bg-green-600' : 'bg-zinc-600' }}"
                            role="switch"
                            aria-checked="{{ $status === 'active' ? 'true' : 'false' }}"
                        >
                            <span class="sr-only">Toggle project status</span>
                            <span
                                aria-hidden="true"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $status === 'active' ? 'translate-x-5' : 'translate-x-0' }}"
                            ></span>
                        </button>
                        <span class="text-sm {{ $status === 'active' ? 'text-green-400' : 'text-gray-400' }}">
                            {{ $status === 'active' ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                    @if($isEditing)
                        Save Changes
                    @else
                        Create Project
                    @endif
                </button>
            </div>
        </form>

        @if($isEditing)
        <!-- Membership Management Section -->
        <div class="mt-10 pt-6 border-t border-zinc-700">
            <h2 class="text-xl font-bold mb-4">Project Members</h2>

            <!-- Current Members -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">Current Members</h3>
                <div class="bg-zinc-700 rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-zinc-600">
                        <thead class="bg-zinc-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">User</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-600">
                            @foreach($memberships as $membership)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-white">{{ $membership->user->name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $membership->primary ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ ucfirst($membership->role) }} {{ $membership->primary ? '(Primary)' : '' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($membership->status === 'active') bg-green-100 text-green-800
                                            @elseif($membership->status === 'pending') bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800 @endif">
                                            {{ ucfirst($membership->status) }}
                                        </span>
                                    </td>
                                    <td class="w-0% px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-4">
                                            @can('removeMember', $project)
                                                @if(!$membership->primary && $membership->status === 'active')
                                                    <button wire:click="setPrimaryMember({{ $membership->id }})" wire:confirm="Are you sure you want to set this member as the primary owner? This will remove primary status from any other members." class="text-blue-400 hover:text-blue-300">
                                                        Make Primary Owner
                                                    </button>
                                                @endif
                                            @endcan
                                            @if($membership->user_id === auth()->id() && !$membership->primary)
                                                <button wire:click="removeMember({{ $membership->id }})" wire:confirm="Are you sure you want to leave this project?" class="text-red-400 hover:text-red-300">
                                                    Leave Project
                                                </button>
                                            @elseif($membership->user_id !== auth()->id() && (!$membership->primary || $project->memberships()->where('primary', true)->count() > 1))
                                                @can('removeMember', $project)
                                                <button wire:click="removeMember({{ $membership->id }})" wire:confirm="Are you sure you want to remove this member?" class="text-red-400 hover:text-red-300">
                                                    Remove
                                                </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add New Member -->
            @can('addMember', $project)
            <div>
                <h3 class="text-lg font-semibold mb-2">Add New Member</h3>
                <p class="text-sm text-gray-400 mb-2">Note: Only one member can be the primary owner of the project at a time. Only primary owners can transfer ownership to other members. Non-primary members can leave the project at any time.</p>
                <div class="bg-zinc-700 p-4 rounded-lg">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label for="newMemberName" class="block text-sm font-medium mb-1">Username</label>
                            <input type="text" id="newMemberName" wire:model="newMemberName" class="w-full bg-zinc-600 border border-zinc-500 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter username">
                            @error('newMemberName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="newMemberRole" class="block text-sm font-medium mb-1">Role</label>
                            <select id="newMemberRole" wire:model="newMemberRole" class="w-full bg-zinc-600 border border-zinc-500 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach($roles as $role)
                                    <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                            @error('newMemberRole') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button wire:click="addMember" type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                            Send Invitation
                        </button>
                    </div>
                </div>
                <p class="text-sm text-gray-400 mt-2">An invitation will be sent to the user. They must accept the invitation to join the project.</p>
            </div>
            @else
            <div>
                <h3 class="text-lg font-semibold mb-2">Add New Member</h3>
                <p class="text-sm text-gray-400">You don't have permission to add members to this project.</p>
            </div>
            @endcan
        </div>

        <!-- Project Deletion Section -->
        @can('delete', $project)
        <div class="mt-10 pt-6 border-t border-zinc-700">
            <h2 class="text-xl font-bold mb-4 text-red-500">Delete Project</h2>
            <div class="bg-zinc-700 p-4 rounded-lg">
                <p class="text-sm text-gray-300 mb-4">
                    <strong>Warning:</strong> Deleting a project is a serious action. The project will be soft-deleted and remain visible only to project members for 14 days before being permanently deleted.
                </p>

                <div class="mb-4">
                    <label for="deleteConfirmation" class="block text-sm font-medium mb-1">
                        To confirm deletion, type the project name: <span class="font-bold">{{ $project->name }}</span>
                    </label>
                    <input
                        type="text"
                        id="deleteConfirmation"
                        wire:model="deleteConfirmation"
                        class="w-full bg-zinc-600 border border-zinc-500 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="Type the project name to confirm"
                    >
                    @error('deleteConfirmation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end">
                    <button
                        type="button"
                        wire:click="deleteProject"
                        wire:confirm="Are you absolutely sure you want to delete this project? This action cannot be easily undone."
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md"
                    >
                        Delete Project
                    </button>
                </div>
            </div>
        </div>
        @endcan
        @endif
    </div>
</div>
