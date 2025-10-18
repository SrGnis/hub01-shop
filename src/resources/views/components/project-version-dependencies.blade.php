@props(['version', 'project'])

@if($version->dependencies->count() > 0)
    <x-card title="Dependencies" separator>
        <div class="space-y-4">
            @foreach(['required', 'optional', 'embedded'] as $type)
                @php
                    $deps = $version->dependencies->where('dependency_type', $type);
                @endphp

                @if($deps->count() > 0)
                    <div>
                        <h3 class="text-sm font-semibold mb-2 capitalize">{{ ucfirst($type) }} Dependencies</h3>
                        <div class="space-y-2">
                            @foreach($deps as $dependency)
                                <div class="flex items-center justify-between p-3 border border-base-content/10 rounded-lg">
                                    <div class="flex-grow">
                                        @if($dependency->dependencyProjectVersion)
                                            <a href="{{ route('project.version.show', ['projectType' => $dependency->dependencyProjectVersion->project->projectType, 'project' => $dependency->dependencyProjectVersion->project, 'version_key' => $dependency->dependencyProjectVersion]) }}"
                                               class="font-semibold hover:text-primary transition-colors">
                                                {{ $dependency->dependencyProjectVersion->project->name }}
                                            </a>
                                            <div class="text-sm text-base-content/60">
                                                Version: {{ $dependency->dependencyProjectVersion->version }}
                                            </div>
                                        @elseif($dependency->dependencyProject)
                                            <a href="{{ route('project.show', ['projectType' => $dependency->dependencyProject->projectType, 'project' => $dependency->dependencyProject]) }}"
                                               class="font-semibold hover:text-primary transition-colors">
                                                {{ $dependency->dependencyProject->name }}
                                            </a>
                                            <div class="text-sm text-base-content/60">
                                                Version: Any
                                            </div>
                                        @else
                                            <div class="font-semibold">{{ $dependency->dependency_name }}</div>
                                            <div class="text-sm text-base-content/60">
                                                Version: {{ $dependency->dependency_version ?? 'Any' }}
                                            </div>
                                        @endif
                                    </div>
                                    <x-badge :value="ucfirst($type)" class="badge-sm {{ \App\Enums\DependencyType::from($type)->bgColorClass() }}" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </x-card>
@endif

