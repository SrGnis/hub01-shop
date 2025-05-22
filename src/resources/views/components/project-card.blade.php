@props(['project'])

<div class="mod-container bg-zinc-800 mb-2 text-gray-300 p-4 rounded-md">
    <div class="mod-card flex flex-col lg:flex-row">
        <div class="mod-card-content flex flex-col lg:flex-row gap-2 lg:mr-3 mr-0">
            <div class="mod-card-image flex gap-4 shrink-0 items-start">
                <a href="{{ route('project.show', ['projectType' => $project->projectType,'project' => $project]) }}" class="hover:opacity-80 transition-opacity flex-shrink-0">
                    <img src="{{ $project->getLogoUrl() }}" class="w-32 h-32 object-cover bg-zinc-700 rounded-md" alt="{{ $project->name }} Logo">
                </a>
                <div class="mod-card-basic-info lg:hidden flex-grow">
                    <div class="mod-card-name font-bold text-lg break-words">
                        <a href="{{ route('project.show', ['projectType' => $project->projectType,'project' => $project]) }}" class="text-indigo-400 hover:text-indigo-300">
                            {{ $project->pretty_name ?? $project->name ?? 'Unnamed Project' }}
                        </a>
                    </div>
                    <div class="mod-card-author text-lg break-words">
                        by
                        <span class="italic">
                            {{ $project->owner->first() ? $project->owner->first()->name : 'Unknown' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="mod-card-info flex flex-col lg:justify-between">
                <div class="flex flex-col gap-2">
                    <div class="mod-card-basic-info hidden lg:flex lg:flex-row gap-1">
                        <div class="mod-card-name font-bold text-lg">
                            <a href="{{ route('project.show', ['projectType' => $project->projectType,'project' => $project]) }}" class="text-indigo-400 hover:text-indigo-300">
                                {{ $project->pretty_name ?? $project->name ?? 'Unnamed Project' }}
                            </a>
                        </div>
                        <div class="mod-card-author text-lg ml-1">
                            by
                            <span class="italic">
                                {{ $project->owner->first() ? $project->owner->first()->name : 'Unknown' }}
                            </span>
                        </div>
                    </div>
                    <div class="mod-card-description text-sm">
                        {{ $project->summary ?? 'No description available' }}
                    </div>
                </div>
                <div class="mod-card-tags flex flex-wrap justify-self-end gap-1">
                    @if($project->tags->count() > 0)
                        @foreach ($project->tags as $tag)
                            <div class="mod-card-tag flex gap-1 flex-nowrap items-center">
                                @svg($tag->icon, 'w-5 h-5')
                                <span>{{ $tag->name }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="mod-card-stats flex flex-row flex-wrap lg:flex-col lg:justify-center lg:ml-auto shrink-0 gap-1">
            <div>
                @svg('lucide-download', 'w-5 h-5 inline')
                @if(isset($project->versions_sum_downloads))
                    {{ $project->versions_sum_downloads }} downloads
                @else
                    {{ $project->downloads }} downloads
                @endif
            </div>
            <div>
                @svg('lucide-calendar', 'w-5 h-5 inline')
                {{ $project->created_at ? $project->created_at->diffForHumans() : 'Unknown' }}
            </div>
            <div>
                @svg('lucide-refresh-cw', 'w-5 h-5 inline')
                @if(isset($project->versions_max_release_date))
                    {{Carbon\Carbon::parse($project->versions_max_release_date)->diffForHumans()}}
                @else
                    {{ Carbon\Carbon::parse($project->recent_release_date)->diffForHumans() }}
                @endif
            </div>
        </div>
    </div>
</div>
