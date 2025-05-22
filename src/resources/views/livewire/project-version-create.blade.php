<div class="w-full lg:w-10/12 m-auto py-6">
    <!-- Back Button -->
    <div class="mb-6 flex justify-between">
        <a href="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}" class="inline-flex items-center text-indigo-400 hover:text-indigo-300">
            @svg('lucide-arrow-left', 'w-5 h-5 mr-1')
            Back to Project
        </a>
    </div>

    <div class="bg-zinc-800 text-gray-300 p-6 rounded-lg">
        <h1 class="text-2xl font-bold mb-6">Upload New Version for: {{ $project->pretty_name }}</h1>

        <form wire:submit="save" class="space-y-6">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium mb-1">Version Name</label>
                <input type="text" id="name" wire:model="name" class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Version Number -->
            <div>
                <label for="version" class="block text-sm font-medium mb-1">Version Number</label>
                <input type="text" id="version" wire:model="version" placeholder="e.g. 1.0.0" class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('version') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Release Type -->
            <div>
                <label for="release_type" class="block text-sm font-medium mb-1">Release Type</label>
                <select id="release_type" wire:model="release_type" class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="alpha">Alpha</option>
                    <option value="beta">Beta</option>
                    <option value="rc">Release Candidate</option>
                    <option value="release">Release</option>
                </select>
                @error('release_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Release Date -->
            <div>
                <label for="release_date" class="block text-sm font-medium mb-1">Release Date</label>
                <input type="date" id="release_date" wire:model="release_date" class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('release_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Changelog -->
            <div x-data="{ mode: 'code' }">
                <div class="flex justify-between items-center mb-1">
                    <label for="changelog" class="text-sm font-medium">Changelog (Markdown)</label>
                    <div class="flex bg-zinc-700 rounded-md p-0.5">
                        <button type="button"
                                @click="mode = 'code'"
                                :class="{ 'bg-zinc-600': mode === 'code' }"
                                class="px-3 py-1 text-sm rounded-md">
                            @svg('lucide-code', 'w-4 h-4 inline-block mr-1')
                            Code
                        </button>
                        <button type="button"
                                @click="mode = 'preview'"
                                :class="{ 'bg-zinc-600': mode === 'preview' }"
                                class="px-3 py-1 text-sm rounded-md">
                            @svg('lucide-eye', 'w-4 h-4 inline-block mr-1')
                            Preview
                        </button>
                    </div>
                </div>
                <div>
                    <textarea
                        x-show="mode === 'code'"
                        id="changelog"
                        wire:model.live="changelog"
                        rows="6"
                        class="w-full bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                    ></textarea>
                    <div
                        x-show="mode === 'preview'"
                        x-cloak
                        class="bg-zinc-700 border border-zinc-600 rounded-md px-3 py-2 min-h-[146px]"
                    >
                        <x-markdown class="prose prose-invert">{{ $changelog }}</x-markdown>
                    </div>
                </div>
                @error('changelog') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Files -->
            <div>
                <label class="block text-sm font-medium mb-2">Upload Files</label>
                <div
                    x-data="{ uploading: false, progress: 0 }"
                    x-on:livewire-upload-start="uploading = true"
                    x-on:livewire-upload-finish="uploading = false"
                    x-on:livewire-upload-error="uploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                >
                    <div class="flex items-center justify-center w-full">
                        <label for="file-upload" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-zinc-600 rounded-lg cursor-pointer bg-zinc-700 hover:bg-zinc-600">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                @svg('lucide-upload-cloud', 'w-10 h-10 mb-3 text-gray-400')
                                <p class="mb-2 text-sm text-gray-400">
                                    <span class="font-semibold">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-gray-400">
                                    ZIP, JAR, or other project files (max 100MB)
                                </p>
                            </div>
                            <input id="file-upload" type="file" wire:model="files" class="hidden" multiple />
                        </label>
                    </div>

                    <!-- Progress Bar -->
                    <div x-show="uploading" class="mt-2">
                        <div class="w-full bg-zinc-700 rounded-full h-2.5">
                            <div class="bg-indigo-600 h-2.5 rounded-full" x-bind:style="'width: ' + progress + '%'"></div>
                        </div>
                        <p class="text-xs text-center mt-1" x-text="'Uploading: ' + progress + '%'"></p>
                    </div>

                    <!-- File List -->
                    @if(count($files) > 0)
                        <div class="mt-4 space-y-2">
                            <h4 class="text-sm font-medium">Selected Files:</h4>
                            <ul class="space-y-1">
                                @foreach($files as $index => $file)
                                    <li class="flex items-center justify-between bg-zinc-700 p-2 rounded">
                                        <span class="text-sm truncate">{{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 2) }} KB)</span>
                                        <button type="button" wire:click="$set('files.{{ $index }}', null)" class="text-red-400 hover:text-red-300">
                                            @svg('lucide-x', 'w-4 h-4')
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @error('files.*') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Dependencies -->
            <div>
                <h3 class="text-lg font-medium mb-4">Dependencies</h3>
                <div class="space-y-4">
                    @foreach($dependencies as $index => $dependency)
                        <div class="bg-zinc-700 p-4 rounded-lg space-y-3">
                            <div class="flex justify-between items-center">
                                <h4 class="font-medium">Dependency #{{ $index + 1 }}</h4>
                                <button type="button" wire:click="removeDependency({{ $index }})" class="text-red-400 hover:text-red-300">
                                    @svg('lucide-trash-2', 'w-4 h-4')
                                </button>
                            </div>

                            <!-- Dependency Type -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Type</label>
                                <select wire:model.live="dependencies.{{ $index }}.type"
                                        class="w-full bg-zinc-600 border border-zinc-500 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="required">Required</option>
                                    <option value="optional">Optional</option>
                                    <option value="embedded">Embedded</option>
                                </select>
                            </div>

                            <!-- Project Selection -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Project Slug</label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        wire:model.live.debounce.500ms="dependencies.{{ $index }}.project_slug"
                                        placeholder="Enter project slug"
                                        class="w-full bg-zinc-600 border border-zinc-500 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                    @if(isset($dependencies[$index]['project_slug']) && !empty($dependencies[$index]['project_slug']) && !$dependencies[$index]['project_id'])
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            @svg('lucide-x-circle', 'w-5 h-5 text-red-500')
                                        </div>
                                    @elseif($dependencies[$index]['project_id'])
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            @svg('lucide-check-circle', 'w-5 h-5 text-green-500')
                                        </div>
                                    @endif
                                </div>
                                @if(isset($dependencies[$index]['project_slug']) && !empty($dependencies[$index]['project_slug']) && !$dependencies[$index]['project_id'])
                                    <p class="mt-1 text-sm text-red-500">Project not found</p>
                                @endif
                            </div>

                            <!-- Version Selection (Optional) -->
                            @if($dependencies[$index]['project_id'])
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <label class="block text-sm font-medium">Specific Version</label>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                   wire:model.live="dependencies.{{ $index }}.has_specific_version"
                                                   class="sr-only peer">
                                            <div class="w-9 h-5 bg-zinc-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                                        </label>
                                    </div>
                                    @if($dependencies[$index]['has_specific_version'])
                                        <x-forms.select
                                            :options="$this->getVersionOptions($dependencies[$index]['project_id'])"
                                            :property="'dependencies.' . $index . '.version_id'"
                                            :searchable="true" />
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <button type="button"
                            wire:click.prevent="addDependency"
                            class="w-full bg-zinc-700 hover:bg-zinc-600 text-white px-4 py-2 rounded-md flex items-center justify-center gap-2">
                        @svg('lucide-plus', 'w-4 h-4')
                        Add Dependency
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                    Upload Version
                </button>
            </div>
        </form>
    </div>
</div>
