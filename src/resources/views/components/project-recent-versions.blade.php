@props(['project'])

<x-card title="Recent Versions" separator>
    @if($project->recent_versions->count() > 0)
        <div class="space-y-3">
            @foreach ($project->recent_versions as $version)
                @php
                    $firstFile = $version->files->first();
                @endphp
                <div class="flex justify-between items-start gap-3 pb-3 border-b border-base-content/10 last:border-b-0 last:pb-0">
                    <div class="flex-grow">
                        <div class="font-semibold text-sm">
                            <a
                                href="{{ route('project.version.show', ['projectType' => $project->projectType, 'project' => $project, 'version_key' => $version]) }}"
                                class="link link-hover"
                            >
                                {{ $version->name }} - {{ $version->version }}
                            </a>
                        </div>
                        <div class="text-xs text-base-content/60 mt-1">
                            Released: {{ $version->release_date->format('Y-m-d') }}
                        </div>
                        <div class="text-xs text-base-content/60">
                            {{ number_format($version->downloads) }} downloads
                        </div>
                    </div>
                    @if ($firstFile)
                        <x-button
                            link="{{ route('file.download', ['projectType' => $project->projectType, 'project' => $project, 'version' => $version, 'file' => $firstFile]) }}"
                            icon="download"
                            class="btn-sm btn-primary"
                            tooltip="Download {{ $firstFile->name }}"
                            no-wire-navigate
                        />
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8">
            <x-icon name="file-x" class="w-12 h-12 mx-auto mb-2" />
            <p class="text-base-content/60">No versions available</p>
        </div>
    @endif
</x-card>
