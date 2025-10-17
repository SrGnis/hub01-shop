@props(['project'])

@php
    $hasLinks = (isset($project->website) && $project->website) || 
                (isset($project->issues) && $project->issues) || 
                (isset($project->source) && $project->source);
@endphp

@if($hasLinks)
    <x-card title="Links" separator>
        <div class="space-y-2">
            @if (isset($project->website) && $project->website)
                <x-button
                    link="{{ $project->website }}"
                    external
                    icon="globe"
                    label="Website"
                    class="w-full btn-ghost justify-start"
                />
            @endif

            @if (isset($project->issues) && $project->issues)
                <x-button
                    link="{{ $project->issues }}"
                    external
                    icon="bug"
                    label="Issue Tracker"
                    class="w-full btn-ghost justify-start"
                />
            @endif

            @if (isset($project->source) && $project->source)
                <x-button
                    link="{{ $project->source }}"
                    external
                    icon="code"
                    label="Source Code"
                    class="w-full btn-ghost justify-start"
                />
            @endif
        </div>
    </x-card>
@endif

