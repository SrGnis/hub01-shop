@props(['dependency', 'project', 'version', 'badgeColor' => 'bg-red-700'])

<div class="dependency-item border border-zinc-700 rounded p-3">
    {{-- Project name section --}}
    <div class="font-semibold">
        @if($dependency->dependency_project_version_id && $dependency->dependencyProjectVersion && $dependency->dependencyProjectVersion->project)
            <a href="{{ route('project.show', ['projectType' => $dependency->dependencyProjectVersion->project->projectType, 'project' => $dependency->dependencyProjectVersion->project]) }}" class="text-indigo-400 hover:text-indigo-300">
                {{ $dependency->dependencyProjectVersion->project->name }}
            </a>
        @elseif($dependency->dependency_project_id && $dependency->dependencyProject)
            <a href="{{ route('project.show', ['projectType' => $dependency->dependencyproject->projectType, 'project' => $dependency->dependencyProject]) }}" class="text-indigo-400 hover:text-indigo-300">
                {{ $dependency->dependencyProject->name }}
            </a>
        @else
            {{ $dependency->dependency_name ?? 'Unknown Project' }}
        @endif
    </div>

    {{-- Version and badge section --}}
    <div class="flex justify-between items-center mt-1">
        <div class="text-sm">
            @if($dependency->dependency_project_version_id && $dependency->dependencyProjectVersion && $dependency->dependencyProjectVersion->project)
                Version:
                <a href="{{ route('project.version.show', ['projectType' => $dependency->dependencyProjectVersion->project->projectType, 'project' => $dependency->dependencyProjectVersion->project, 'version_key' => $dependency->dependencyProjectVersion]) }}" class="text-indigo-400 hover:text-indigo-300">
                    {{ $dependency->dependencyProjectVersion->version }}
                </a>
                <span class="ml-1 text-xs {{ $dependency->dependencyProjectVersion->bg_color_class }} px-1 py-0.5 rounded">{{ $dependency->dependencyProjectVersion->display_name }}</span>
            @elseif($dependency->dependency_version)
                Version: {{ $dependency->dependency_version }}
            @else
                Any version
            @endif
        </div>
        <div class="text-xs {{ $badgeColor }} px-2 py-1 rounded">{{ $dependency->display_name }}</div>
    </div>
</div>
