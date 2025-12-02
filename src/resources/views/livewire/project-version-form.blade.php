<div class="w-full lg:w-10/12 m-auto py-6">
    <!-- Back Button -->
    <div class="mb-6">
        <x-button link="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}" icon="lucide-arrow-left" label="Back to Project" />
    </div>

    <x-card>
        <h1 class="text-2xl font-bold mb-6">
            @if($isEditing)
                Edit Version: {{ $version->name }}
            @else
                Upload New Version for: {{ $project->pretty_name }}
            @endif
        </h1>

        <form wire:submit="save" class="space-y-6">
            <!-- Name -->
            <x-input label="Version Name" wire:model="name" required />

            <!-- Version Number -->
            <x-input label="Version Number" wire:model="version_number" placeholder="e.g. 1.0.0" required />

            <!-- Release Type -->
            <x-select label="Release Type" wire:model="release_type" :options="[
                ['id' => 'alpha', 'name' => 'Alpha'],
                ['id' => 'beta', 'name' => 'Beta'],
                ['id' => 'rc', 'name' => 'Release Candidate'],
                ['id' => 'release', 'name' => 'Release'],
            ]" required />

            <!-- Release Date -->
            <x-datetime label="Release Date" wire:model="release_date" type="date" required />

            <!-- Changelog -->
            <div x-data="{ mode: 'code' }">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium">Changelog (Markdown)</label>
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
                        wire:model.live="changelog"
                        height="300px"
                        language="markdown"
                        hint="Markdown"
                        wrap=1
                    />
                </div>
                <div x-show="mode === 'preview'" x-cloak class="bg-base-200 rounded-lg p-4 min-h-[242px]">
                    <x-markdown class="prose prose-invert max-w-none" flavor="github">{{ $changelog }}</x-markdown>
                </div>
            </div>

            <!-- Version Tags -->
            <div>
                <label class="text-sm font-medium mb-2 block">Tags</label>
                @foreach($availableTagGroups as $tagGroup)
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">{{ $tagGroup->name }}</h3>
                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($tagGroup->tags as $tag)
                                <x-checkbox
                                    wire:model="selectedTags"
                                    value="{{ $tag->id }}"
                                    class="text-sm"
                                >
                                    <x-slot:label class="flex items-center gap-2">
                                        <div class="flex items-center gap-2">
                                            @if($tag->icon)
                                                <x-icon :name="$tag->icon" class="w-4 h-4" />
                                            @endif
                                            {{ $tag->name }}
                                        </div>
                                    </x-slot:label>
                                </x-checkbox>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @error('selectedTags') <span class="text-error text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Existing Files (Edit Mode) -->
            @if($isEditing && count($existingFiles) > 0)
                <div>
                    <h3 class="text-lg font-medium mb-2">Existing Files</h3>
                    <div class="space-y-2">
                        @foreach($existingFiles as $index => $file)
                            @if(!isset($file['delete']) || !$file['delete'])
                                <div class="flex items-center justify-between bg-base-200 p-2 rounded">
                                    <span class="text-sm truncate">{{ $file['name'] }} ({{ number_format($file['size'] / 1024, 2) }} KB)</span>
                                    <x-button type="button" wire:click="removeExistingFile({{ $file['id'] }})" icon="lucide-trash-2" class="btn-sm btn-ghost text-error" />
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Files -->
            <x-file
                wire:model="files"
                label="{{ $isEditing ? 'Upload Additional Files' : 'Upload Files' }}"
                multiple
                hint="ZIP or other project files (max 100MB)"
            />
            
            @if(count($files) > 0)
                <div class="mt-4 space-y-2">
                    <h4 class="text-sm font-medium">Selected Files:</h4>
                    <ul class="space-y-1">
                        @foreach($files as $index => $file)
                            <li class="flex items-center justify-between bg-base-200 p-2 rounded">
                                <span class="text-sm truncate">{{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 2) }} KB)</span>
                                <x-button type="button" wire:click="$set('files.{{ $index }}', null)" icon="lucide-x" class="btn-sm btn-ghost text-error" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Dependencies -->
            <div>
                <h3 class="text-lg font-medium mb-4">Dependencies</h3>
                <div class="space-y-4">
                    @foreach($dependencies as $index => $dependency)
                        <div class="bg-base-200 p-4 rounded-lg space-y-3">
                            <div class="flex justify-between items-center">
                                <h4 class="font-medium">Dependency #{{ $index + 1 }}</h4>
                                <x-button type="button" wire:click="removeDependency({{ $index }})" icon="lucide-trash-2" class="btn-sm btn-ghost text-error" />
                            </div>

                            <!-- Dependency Type -->
                            <x-select label="Type" wire:model.live="dependencies.{{ $index }}.type" :options="[
                                ['id' => 'required', 'name' => 'Required'],
                                ['id' => 'optional', 'name' => 'Optional'],
                                ['id' => 'embedded', 'name' => 'Embedded'],
                            ]" />

                            <!-- Dependency Mode Selection -->
                            <x-select label="Dependency Mode" wire:model.live="dependencies.{{ $index }}.mode" :options="[
                                ['id' => 'linked', 'name' => 'Link to Project'],
                                ['id' => 'manual', 'name' => 'Manual Entry'],
                            ]" />

                            @if(isset($dependencies[$index]['mode']) && $dependencies[$index]['mode'] === 'linked')
                                <!-- Project Selection (Linked Mode) -->
                                <div>
                                    <x-input
                                        label="Project Slug"
                                        wire:model.live.debounce.500ms="dependencies.{{ $index }}.project_slug"
                                        placeholder="Enter project slug"
                                    >
                                        <x-slot:append>
                                            @if(isset($dependencies[$index]['project_slug']) && !empty($dependencies[$index]['project_slug']) && !$dependencies[$index]['project_id'])
                                                <x-icon name="lucide-x-circle" class="w-5 h-5 text-error" />
                                            @elseif($dependencies[$index]['project_id'])
                                                <x-icon name="lucide-check-circle" class="w-5 h-5 text-success" />
                                            @endif
                                        </x-slot:append>
                                    </x-input>
                                    @if(isset($dependencies[$index]['project_slug']) && !empty($dependencies[$index]['project_slug']) && !$dependencies[$index]['project_id'])
                                        <p class="mt-1 text-sm text-error">Project not found</p>
                                    @endif
                                </div>

                                <!-- Version Selection (Optional) -->
                                @if($dependencies[$index]['project_id'])
                                    <div>
                                        <x-checkbox label="Specific Version" wire:model.live="dependencies.{{ $index }}.has_specific_version" />
                                        
                                        @if($dependencies[$index]['has_specific_version'])
                                            <x-select
                                                label="Version"
                                                wire:model="dependencies.{{ $index }}.version_id"
                                                :options="$this->getVersionOptions($dependencies[$index]['project_id'])"
                                                placeholder="Select a version"
                                            />
                                        @endif
                                    </div>
                                @endif
                            @else
                                <!-- Manual Entry Mode -->
                                <x-input
                                    label="Project Name"
                                    wire:model="dependencies.{{ $index }}.dependency_name"
                                    placeholder="Enter project name"
                                />

                                <div>
                                    <x-checkbox label="Specific Version" wire:model.live="dependencies.{{ $index }}.has_manual_version" />
                                    
                                    @if(isset($dependencies[$index]['has_manual_version']) && $dependencies[$index]['has_manual_version'])
                                        <x-input
                                            label="Version"
                                            wire:model="dependencies.{{ $index }}.dependency_version"
                                            placeholder="Enter version (e.g. 1.0.0)"
                                        />
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <x-button type="button" wire:click="addDependency" icon="lucide-plus" label="Add Dependency" class="w-full btn-outline" />
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <x-button type="submit" label="{{ $isEditing ? 'Save Changes' : 'Upload Version' }}" class="btn-primary" />
            </div>
        </form>

        @if($isEditing)
            <!-- Delete Version Section -->
            <div class="divider my-8"></div>
            <h2 class="text-xl font-bold mb-4 text-error">Delete Version</h2>
            <x-alert title="Warning" icon="lucide-alert-triangle" description="Deleting a version will permanently remove it and all its files. This action cannot be undone." class="alert-error mb-4" />

            <form wire:submit.prevent="deleteVersion" class="space-y-4">
                <x-input label="Type {{ $version->version }} to confirm deletion" wire:model="deleteConfirmation" placeholder="Enter version number to confirm" />
                
                <div class="flex justify-end">
                    <x-button type="submit" label="Delete Version" icon="lucide-trash-2" class="btn-error" />
                </div>
            </form>
        @endif
    </x-card>
</div>
