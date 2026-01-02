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
            <x-input spinner label="Version Number" wire:model.blur="version_number" placeholder="e.g. 1.0.0" required />

            <!-- Release Type -->
            <x-select label="Release Type" wire:model="release_type" :options="[
                ['id' => 'alpha', 'name' => 'Alpha'],
                ['id' => 'beta', 'name' => 'Beta'],
                ['id' => 'rc', 'name' => 'Release Candidate'],
                ['id' => 'release', 'name' => 'Release'],
            ]" required />

            <!-- Release Date -->
            <x-datetime label="Release Date" wire:model="release_date" type="date" required />

            <!-- Changelog (Markdown) -->
            <div x-data="{ mode: 'code' }">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium">Changelog (Markdown)</label>
                    <div class="join">
                        <button type="button" @click="mode = 'code'" :class="{ 'join-item btn-active': mode === 'code' }" class="join-item btn btn-sm">
                            <x-icon name="lucide-code" class="w-4 h-4" /> Code
                        </button>
                        <button type="button" @click="$wire.refreshMarkdown().then(() => mode = 'preview')" :class="{ 'join-item btn-active': mode === 'preview' }" class="join-item btn btn-sm">
                            <x-icon name="lucide-eye" class="w-4 h-4" /> Preview
                        </button>
                    </div>
                </div>
                <div wire:loading.remove wire:target="refreshMarkdown" x-show="mode === 'code'">
                    <x-code
                        wire:model="changelog"
                        height="300px"
                        language="markdown"
                        hint="Markdown"
                        wrap=1
                    />
                </div>
                <div wire:loading.flex wire:target="refreshMarkdown" class="bg-base-200 rounded-lg p-4 min-h-[242px] w-full flex items-center justify-center">
                    <span class="loading loading-spinner w-10 h-10"></span>
                </div>
                <div x-show="mode === 'preview'" x-cloak class="bg-base-200 rounded-lg p-4 min-h-[242px]">
                    <x-markdown class="prose prose-invert max-w-none" flavor="github">{{ $changelog }}</x-markdown>
                </div>
            </div>

            <!-- Version Tags -->
            <div>
                <label class="text-sm font-medium mb-2 block">Tags</label>
                @foreach($this->availableTagGroups as $tagGroup)
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

            <!-- Files Section -->
            <div>
                <!-- File Upload -->
                <x-file
                    spinner
                    wire:model="files"
                    label="{{ $isEditing ? 'Upload Additional Files' : 'Upload Files' }}"
                    multiple
                    hint="ZIP or other project files (max 100MB)"
                    hideProgress
                />

                <!-- This should hide if there are no existing files and no new files -->
                @php
                    $hasVisibleExistingFiles = $isEditing && collect($existingFiles)->contains(fn($file) => !($file['delete'] ?? false));
                @endphp
                @if($hasVisibleExistingFiles || count($files) > 0)
                    <div class="mt-4 bg-base-100 border border-base-300 rounded-lg p-4">
                        <h3 class="text-lg font-medium mb-4">Project Files</h3>

                        <div class="space-y-4">
                            <!-- This should hide if I delete all existing files -->
                            @if($hasVisibleExistingFiles)
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <x-icon name="lucide-file-check" class="w-4 h-4 text-success" />
                                        <h4 class="text-sm font-medium text-success">Existing Files</h4>
                                    </div>
                                    <div class="space-y-2">
                                        @foreach($existingFiles as $index => $file)
                                            @if(!isset($file['delete']) || !$file['delete'])
                                                <div class="flex items-center justify-between bg-success/10 border border-success/20 p-3 rounded">
                                                    <span class="text-sm truncate">{{ $file['name'] }} ({{ number_format($file['size'] / 1024, 2) }} KB)</span>
                                                    <x-button spinner type="button" wire:click="removeExistingFile({{ $file['id'] }})" icon="trash-2" class="btn-sm btn-ghost text-error" />
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Divider between existing and new files -->
                            @if($hasVisibleExistingFiles && count($files) > 0)
                                <div class="divider my-2"></div>
                            @endif

                            <!-- Newly Selected Files -->
                            @if(count($files) > 0)
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <x-icon name="lucide-file-plus" class="w-4 h-4 text-info" />
                                        <h4 class="text-sm font-medium text-info">Newly Selected Files</h4>
                                    </div>
                                    <div class="space-y-2">
                                        @foreach($files as $index => $file)
                                            <div class="flex items-center justify-between bg-info/10 border border-info/20 p-3 rounded">
                                                <span class="text-sm truncate">{{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 2) }} KB)</span>
                                                <x-button spinner type="button" wire:click="removeNewFile({{ $index }})" icon="trash-2" class="btn-sm btn-ghost text-error" />
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Dependencies -->
            <div>
                <h3 class="text-lg font-medium mb-4">Dependencies</h3>
                <div class="space-y-4">
                    @foreach($dependencies as $index => $dependency)
                        <div class="bg-base-200 p-4 rounded-lg space-y-3">
                            <div class="flex justify-between items-center">
                                <h4 class="font-medium">Dependency #{{ $index + 1 }}</h4>
                                <x-button spinner type="button" wire:click="removeDependency({{ $index }})" icon="lucide-trash-2" class="btn-sm btn-ghost text-error" />
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

                            <div wire:loading.flex wire:target="dependencies.{{ $index }}.mode" class="justify-center">
                                <span class="loading loading-spinner"></span>
                            </div>
                            <div wire:loading.remove wire:target="dependencies.{{ $index }}.mode">
                                @if(isset($dependencies[$index]['mode']) && $dependencies[$index]['mode'] === 'linked')
                                    <!-- Project Selection (Linked Mode) -->
                                    <div>
                                        <x-input
                                            label="Project Slug"
                                            wire:model.live.debounce.500ms="dependencies.{{ $index }}.project_slug"
                                            placeholder="Enter project slug"
                                            required
                                            spinner
                                        >
                                        </x-input>
                                        @if(isset($dependencies[$index]['project_slug']) && !empty($dependencies[$index]['project_slug']) && !$dependencies[$index]['project_id'])
                                            <p class="mt-1 text-sm text-error">Project not found</p>
                                        @endif
                                    </div>

                                    <!-- Version Selection (Optional) -->
                                    @if($dependencies[$index]['project_id'])
                                        <div>
                                            <x-checkbox label="Specific Version" wire:model.live="dependencies.{{ $index }}.has_specific_version" />

                                            <div wire:loading.flex wire:target="dependencies.{{ $index }}.has_specific_version" class="justify-center">
                                                <span class="loading loading-spinner"></span>
                                            </div>
                                            <div wire:loading.remove wire:target="dependencies.{{ $index }}.has_specific_version">
                                                @if($dependencies[$index]['has_specific_version'])
                                                    <x-select
                                                        label="Version"
                                                        wire:model="dependencies.{{ $index }}.version_id"
                                                        :options="$this->getVersionOptions($dependencies[$index]['project_id'])"
                                                        placeholder="Select a version"
                                                    />
                                                @endif
                                            </div>
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

                                        <div wire:loading.flex wire:target="dependencies.{{ $index }}.has_manual_version" class="justify-center">
                                            <span class="loading loading-spinner"></span>
                                        </div>

                                        <div wire:loading.remove wire:target="dependencies.{{ $index }}.has_manual_version">
                                            @if(isset($dependencies[$index]['has_manual_version']) && $dependencies[$index]['has_manual_version'])
                                                <x-input
                                                    label="Version"
                                                    wire:model="dependencies.{{ $index }}.dependency_version"
                                                    placeholder="Enter version (e.g. 1.0.0)"
                                                />
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <x-button spinner type="button" wire:click="addDependency" icon="lucide-plus" label="Add Dependency" class="w-full btn-outline" />
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
