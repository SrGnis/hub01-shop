@props(['project'])

<div class="bg-zinc-800 text-gray-300 p-4 mb-6 rounded-md">
    <h2 class="text-xl font-bold mb-4">Recent Versions</h2>
    <div class="versions-list">
        @foreach ($project->recent_versions as $version)
            <div class="version-item border-b border-zinc-700 py-3 last:border-b-0">
                <div class="flex justify-between items-center">
                    <div class="font-semibold">{{ $version->name }} - {{ $version->version }}</div>
                    <div class="text-xs {{ $version->bg_color_class }} px-2 py-1 rounded">{{ $version->display_name }}</div>
                </div>
                <div class="flex justify-between items-center mt-2">
                    <div>
                        <div class="text-sm text-gray-400">Released: {{ $version->release_date }}</div>
                        <div class="text-sm">{{ $version->downloads }} downloads</div>
                    </div>
                    <div>
                        <a href="{{ route('project.version.show', ['projectType' => $project->projectType, 'project' => $project, 'version_key' => $version]) }}"
                           class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-md inline-flex items-center gap-1 text-sm">
                            @svg('lucide-download', 'w-4 h-4')
                            Download
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
        
        @if($project->recent_versions->count() === 0)
            <div class="text-center py-8 text-gray-400">
                @svg('lucide-file-x', 'w-12 h-12 mx-auto mb-2')
                <p>No versions available</p>
            </div>
        @endif
    </div>
</div>
