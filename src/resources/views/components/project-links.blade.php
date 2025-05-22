@props(['project'])

@php
    $hasLinks = (isset($project->website) && $project->website) || 
                (isset($project->issues) && $project->issues) || 
                (isset($project->source) && $project->source);
@endphp

@if($hasLinks)
<div class="bg-zinc-800 text-gray-300 p-4 rounded-md">
    <h2 class="text-xl font-bold mb-4">Links</h2>
    <div class="links-list flex flex-col gap-2">
        @if (isset($project->website) && $project->website)
            <a href="{{ $project->website }}" target="_blank" class="flex items-center gap-2 text-indigo-400 hover:text-indigo-300">
                @svg('lucide-globe', 'w-5 h-5')
                Website
            </a>
        @endif

        @if (isset($project->issues) && $project->issues)
            <a href="{{ $project->issues }}" target="_blank" class="flex items-center gap-2 text-indigo-400 hover:text-indigo-300">
                @svg('lucide-alert-triangle', 'w-5 h-5')
                Issue Tracker
            </a>
        @endif

        @if (isset($project->source) && $project->source)
            <a href="{{ $project->source }}" target="_blank" class="flex items-center gap-2 text-indigo-400 hover:text-indigo-300">
                @svg('lucide-code', 'w-5 h-5')
                Source Code
            </a>
        @endif
    </div>
</div>
@endif
