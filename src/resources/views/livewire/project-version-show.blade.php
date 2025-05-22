@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
@endphp

<div class="w-full lg:w-10/12 m-auto py-6">

    <!-- Back Button and Actions -->
    <div class="mb-6 flex justify-between">
        <a href="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}" class="inline-flex items-center text-indigo-400 hover:text-indigo-300">
            @svg('lucide-arrow-left', 'w-5 h-5 mr-1')
            Back to Project
        </a>
        @auth
            @can('editVersion', $project)
            <div class="flex space-x-2">
                <a href="{{ route('project.version.edit', ['projectType' => $project->projectType, 'project' => $project, 'version_key' => $version]) }}" class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-md">
                    @svg('lucide-edit-3', 'w-4 h-4 mr-1')
                    Edit Version
                </a>
            </div>
            @endcan
        @endauth
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column - Main Content -->
        <div class="lg:col-span-8">
            <!-- Version Header -->
            <div class="project-container bg-zinc-800 text-gray-300 p-4 mb-6 rounded-md">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h1 class="text-2xl font-bold">{{ $project->pretty_name }}</h1>
                        <div class="text-lg">
                            Version: <span class="font-semibold">{{ $version->version }}</span>
                            <span class="ml-2 text-sm {{ $version->bg_color_class }} px-2 py-1 rounded">{{ $version->display_name }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm">Released: {{ $version->release_date }}</div>
                        <div class="text-sm">{{ $version->downloads }} downloads</div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="shrink-0">
                        <img src="{{ $project->getLogoUrl() }}" class="w-32 h-32 object-cover bg-zinc-700 rounded-md" alt="{{ $project->name }} Logo">
                    </div>
                    <div class="flex flex-col justify-between">
                        <div>
                            <div class="text-lg mb-2">{{ $version->name }}</div>
                            <div class="text-sm">
                                by <span class="italic">{{ $project->owner->first()->name }}</span>
                            </div>
                        </div>
                        <!-- Project Tags -->
                        <div class="project-card-tags flex flex-wrap gap-1 mt-2">
                            @foreach ($project->tags as $tag)
                                <div class="project-card-tag flex gap-1 flex-nowrap">
                                    @svg($tag->icon, 'w-5 h-5')
                                    {{ $tag->name }}
                                </div>
                            @endforeach
                        </div>

                        <!-- Version Tags -->
                        @if($version->tags->count() > 0)
                        <div class="mt-3">
                            <div class="text-sm text-gray-400 mb-1">Version Tags:</div>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($version->tags as $tag)
                                    <div class="bg-zinc-700 text-xs px-2 py-1 rounded-full flex items-center">
                                        @svg($tag->icon, 'w-4 h-4 mr-1')
                                        {{ $tag->name }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Version Changelog -->
            @if($version->changelog)
            <div class="bg-zinc-800 text-gray-300 p-4 mb-6 rounded-md">
                <h2 class="text-xl font-bold mb-4">Version Changelog</h2>
                <div class="prose prose-invert max-w-none">
                    {{ $version->changelog }}
                </div>
            </div>
            @endif

            <!-- Dependencies Section -->
            <x-dependencies-section :version="$version" :project="$project" />
        </div>

        <!-- Right Column - Sidebar -->
        <div class="lg:col-span-4">
            <!-- Download Section -->
            <div class="bg-zinc-800 text-gray-300 p-4 mb-6 rounded-md">
                <h2 class="text-xl font-bold mb-4">Download</h2>

                @if($version->files->count() > 0)
                    <div class="files-list space-y-4">
                        @foreach($version->files as $file)
                            <div class="file-item border border-zinc-700 rounded p-3">
                                <div class="font-semibold mb-2">{{ $file->name }}</div>
                                <div class="text-sm mb-3">Size: {{ number_format($file->size / 1024, 2) }} KB</div>
                                <a href="{{ route('file.download', ['projectType' => $project->projectType, 'project' => $project, 'version' => $version, 'file' => $file]) }}"
                                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md inline-flex items-center gap-2 w-full justify-center">
                                    @svg('lucide-download', 'w-5 h-5')
                                    Download
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-gray-400">
                        @svg('lucide-file-x', 'w-12 h-12 mx-auto mb-2')
                        <p>No files available for download</p>
                    </div>
                @endif
            </div>

            <!-- Other Versions -->
            @if($project->recent_versions->where('id', '!=', $version->id)->count() > 0)
                <div class="bg-zinc-800 text-gray-300 p-4 mb-6 rounded-md">
                    <h2 class="text-xl font-bold mb-4">Other Versions</h2>
                    <div class="versions-list">
                        @foreach ($project->recent_versions->where('id', '!=', $version->id) as $otherVersion)
                            <div class="version-item border-b border-zinc-700 py-3 last:border-b-0">
                                <div class="flex justify-between items-center">
                                    <div class="font-semibold">{{ $otherVersion->version }}</div>
                                    <div class="text-xs {{ $otherVersion->bg_color_class }} px-2 py-1 rounded">{{ $otherVersion->display_name }}</div>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <div>
                                        <div class="text-sm text-gray-400">Released: {{ $otherVersion->release_date }}</div>
                                        <div class="text-sm">{{ $otherVersion->downloads }} downloads</div>
                                    </div>
                                    <div>
                                        <a href="{{ route('project.version.show', ['projectType' => $project->projectType, 'project' => $project, 'version_key' => $otherVersion]) }}"
                                           class="bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-1 rounded-md inline-flex items-center gap-1 text-sm">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Creators Section -->
            <x-project-creators :project="$project" />

            <!-- Links Section -->
            <x-project-links :project="$project" />
        </div>
    </div>
</div>
