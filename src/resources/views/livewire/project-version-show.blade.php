<div x-data="{ showFilesModal: false }" x-ref="filesContainer">
    <livewire:report-abuse />

    <!-- Back Button and Actions -->
    <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
        <x-button
            link="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project, 'activeTab' => 'versions']) }}"
            icon="arrow-left"
            label="Back to Project"
            class="btn-ghost"
        />
        <div class="flex flex-col lg:flex-row gap-2">
            @auth
                @can('editVersion', $project)
                    <x-button
                        link="{{ route('project.version.edit', ['projectType' => $project->projectType, 'project' => $project, 'version_key' => $version]) }}"
                        icon="pencil"
                        label="Edit Version"
                        class="btn-primary"
                    />
                @endcan
            @endauth
            <x-dropdown right>
                <x-slot:trigger>
                    <x-button icon="ellipsis" class="btn-ghost" />
                </x-slot:trigger>

                <x-menu-item
                    title="Report"
                    class="text-error"
                    icon="flag"
                    @click="$dispatch('open-report-modal', { itemId: {{ $version->id }}, itemType: 'App\\\\Models\\\\ProjectVersion', itemName: '{{ addslashes($version->name) }} - {{ addslashes($version->version) }}' })"
                />
            </x-dropdown>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- COLUMN 1: Main Content (8/12) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- VERSION CARD -->
            <x-card class="!py-3 !px-5">
                <!-- Mobile Layout -->
                <div class="block lg:hidden">
                    <div class="flex gap-4 mb-4">
                        <div class="flex-shrink-0">
                            <img src="{{ $project->getLogoUrl() }}"
                                class="w-28 h-28 object-cover rounded-lg"
                                alt="{{ $project->name }} Logo">
                        </div>
                        <div class="flex-grow min-w-0">
                            <h1 class="text-xl font-bold mb-1">{{ $project->name }}</h1>
                            <div class="text-sm mb-2">
                                Version: <span class="font-semibold">{{ $version->name }} - {{ $version->version }}</span>
                            </div>
                            <div class="text-sm text-base-content/60">
                                by <span class="font-medium">{{ $project->owner->first()->name }}</span>
                            </div>
                        </div>
                    </div>

                    @if($version->files->count() > 0)
                        @if($version->files->count() === 1)
                            <x-button
                                link="{{ route('file.download', ['projectType' => $project->projectType, 'project' => $project, 'version' => $version, 'file' => $version->files->first()]) }}"
                                icon="download"
                                label="Download {{ $version->files->first()->name }}"
                                class="w-full btn-primary mb-4"
                                no-wire-navigate
                            />
                        @else
                            <x-button
                                @click="showFilesModal = true"
                                icon="download"
                                label="Download"
                                class="w-full btn-primary mb-4"
                                no-wire-navigate
                            />
                        @endif
                    @endif

                    @if($project->tags->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($project->tags as $tag)
                                <div class="flex items-center text-xs bg-base-200 px-2 py-1 rounded">
                                    <x-icon :name="$tag->icon" class="w-3 h-3 mr-1" />
                                    {{ $tag->name }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Desktop Layout -->
                <div class="hidden lg:block">
                    <div class="flex gap-6">
                        <!-- Left: Project Image -->
                        <div class="flex-shrink-0 flex items-center">
                            <img src="{{ $project->getLogoUrl() }}"
                                 class="w-32 h-32 object-cover rounded-lg"
                                 alt="{{ $project->name }} Logo">
                        </div>

                        <!-- Center: Project & Version Info -->
                        <div class="flex-grow min-w-0">
                            <h1 class="text-xl font-bold mb-1">{{ $project->name }}</h1>
                            <div class="text-base mb-3 flex items-center gap-2">
                                <span>Version: <span class="font-semibold">{{ $version->name }} - {{ $version->version }}</span></span>
                                <x-badge :value="$version->display_name" class="badge-sm {{ $version->bg_color_class }}" />
                            </div>
                            <div class="mb-4">
                                <span class="text-sm">by <span class="font-medium">{{ $project->owner->first()->name }}</span></span>
                            </div>

                            @if($project->tags->count() > 0)
                                <div class="flex flex-wrap gap-4">
                                    @foreach ($project->tags as $tag)
                                        <div class="flex items-center text-xs bg-base-200 px-2 py-1 rounded">
                                            <x-icon :name="$tag->icon" class="w-3 h-3 mr-1" />
                                            {{ $tag->name }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Right: Download Button -->
                        <div class="flex-shrink-0 flex items-center">
                            @if($version->files->count() > 0)
                                @if($version->files->count() === 1)
                                    <x-button
                                        link="{{ route('file.download', ['projectType' => $project->projectType, 'project' => $project, 'version' => $version, 'file' => $version->files->first()]) }}"
                                        icon="download"
                                        label="Download"
                                        class="btn-primary btn-lg"
                                        no-wire-navigate
                                    />
                                @else
                                    <x-button
                                        @click="showFilesModal = true"
                                        icon="download"
                                        label="Download"
                                        class="btn-primary btn-lg"
                                        no-wire-navigate
                                    />
                                @endif
                            @else
                                <div class="text-center text-sm text-base-content/60">
                                    <x-icon name="file-x" class="w-8 h-8 mx-auto mb-1" />
                                    <p>No files</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- CHANGELOG -->
            @if($version->changelog)
                <x-card title="Changelog" separator>
                    <x-markdown class="prose max-w-none dark:prose-invert">
                        {!! $version->changelog !!}
                    </x-markdown>
                </x-card>
            @endif

            <!-- FILES & DEPENDENCIES -->
            <div class="grid grid-cols-1 {{ $version->dependencies->count() > 0 ? 'lg:grid-cols-2' : '' }} gap-6 items-start">
                <!-- FILES Section -->
                <x-card title="Files" separator>
                    @if($version->files->count() > 0)
                        <div class="space-y-3" x-ref="filesList">
                            @foreach($version->files as $file)
                                <div class="pb-3 border-b border-base-content/10 last:border-b-0 last:pb-0">
                                    <div class="font-semibold text-sm mb-1">{{ $file->name }}</div>
                                    <div class="text-xs text-base-content/60 mb-2">
                                        Size: {{ number_format($file->size / 1024, 2) }} KB
                                    </div>
                                    <x-button
                                        link="{{ route('file.download', ['projectType' => $project->projectType, 'project' => $project, 'version' => $version, 'file' => $file]) }}"
                                        icon="download"
                                        label="Download"
                                        class="w-full btn-sm btn-primary"
                                        no-wire-navigate
                                    />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <x-icon name="file-x" class="w-12 h-12 mx-auto text-base-content/30 mb-2" />
                            <p class="text-sm text-base-content/60">No files available</p>
                        </div>
                    @endif
                </x-card>

                <!-- DEPENDENCIES Section -->
                @if($version->dependencies->count() > 0)
                    <x-project-version-dependencies :version="$version" :project="$project" />
                @endif
            </div>
        </div>

        <!-- COLUMN 2: Sidebar (4/12) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- METADATA CARD -->
            <x-card title="Version Info" separator>
                <div class="space-y-3">
                    <!-- Release Type -->
                    <div>
                        <div class="text-xs text-base-content/60 mb-1">Release Type</div>
                        <x-badge :value="$version->display_name" class="badge-sm badge-soft badge-{{ $version->bg_color_class }}" />
                    </div>

                    <!-- Version Tags -->
                    @if($version->tags->count() > 0)
                        <div>
                            <div class="space-y-2">
                                @foreach ($version->tags->groupBy('tagGroup.name') as $groupName => $tags)
                                    <div>
                                        <div class="text-xs font-medium text-base-content/60 mb-1">{{ $groupName ?? 'Other Tags' }}</div>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($tags as $tag)
                                                <div class="badge badge-sm badge-soft gap-1">
                                                    <x-icon :name="$tag->icon" class="w-3 h-3" />
                                                    {{ $tag->name }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Downloads -->
                    <div>
                        <div class="text-xs text-base-content/60 mb-1">Downloads</div>
                        <div class="flex items-center gap-2">
                            <x-icon name="download" class="w-4 h-4" />
                            <span class="font-bold text-lg">{{ number_format($version->downloads) }}</span>
                        </div>
                    </div>

                    <!-- Release Date -->
                    <div>
                        <div class="text-xs text-base-content/60 mb-1">Release Date</div>
                        <div class="flex items-center gap-2">
                            <x-icon name="calendar" class="w-4 h-4" />
                            <span>{{ $version->release_date->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- CREATORS -->
            <x-project-creators :project="$project" />

            <!-- LINKS -->
            <x-project-links :project="$project" />
        </div>
    </div>

    <!-- FILES MODAL -->
    <x-modal x-show="showFilesModal" title="Available Files" separator>
        <div x-html="$refs.filesList ? $refs.filesList.innerHTML : ''"></div>
    </x-modal>
</div>
