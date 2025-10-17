@props(['project'])

<x-card title="Recent Versions" separator>
    @if($project->recent_versions->count() > 0)
        <div class="space-y-3">
            @foreach ($project->recent_versions as $version)
                <div class="flex justify-between items-start gap-3 pb-3 border-b border-base-content/10 last:border-b-0 last:pb-0">
                    <div class="flex-grow">
                        <div class="font-semibold text-sm">
                            {{ $version->name }} - {{ $version->version }}
                        </div>
                        <div class="text-xs text-base-content/60 mt-1">
                            Released: {{ $version->release_date }}
                        </div>
                        <div class="text-xs text-base-content/60">
                            {{ number_format($version->downloads) }} downloads
                        </div>
                    </div>
                    <x-button
                        link="{{ route('project.version.show', ['projectType' => $project->projectType, 'project' => $project, 'version_key' => $version]) }}"
                        icon="download"
                        class="btn-sm btn-primary"
                    />
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8">
            <x-icon name="file-x" class="w-12 h-12 mx-auto text-base-content/30 mb-2" />
            <p class="text-base-content/60">No versions available</p>
        </div>
    @endif
</x-card>

