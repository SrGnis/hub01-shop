<div class="mod-container border border-black bg-zinc-800 mb-2 text-gray-300 p-4 relative">
    <div class="absolute top-2 right-2 flex gap-2">
        @can('restore', $project)
            <button
                wire:click="restoreProject({{ $project->id }})"
                wire:confirm="Are you sure you want to restore this project? All members will be notified."
                class="bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded cursor-pointer">
                Restore
            </button>
        @endcan
        <div class="bg-red-600 text-white text-xs px-2 py-1 rounded">
            Deleted {{ $project->deleted_at->diffForHumans() }}
        </div>
    </div>
    <div class="mod-card flex flex-col lg:flex-row">
        <div class="mod-card-content flex flex-col lg:flex-row gap-2">
            <div class="mod-card-image flex gap-4 shrink-0">
                <img src="{{ $project->getLogoUrl() }}" class="w-32 h-32 object-cover bg-zinc-700 opacity-50" alt="{{ $project->name }} Logo">
                <div class="mod-card-basic-info lg:hidden">
                    <div class="mod-card-name font-bold text-lg">
                        {{ $project->pretty_name ?? $project->name ?? 'Unnamed Project' }}
                    </div>
                    <div class="mod-card-author text-lg">
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
                            {{ $project->pretty_name ?? $project->name ?? 'Unnamed Project' }}
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
                <div class="flex items-center text-xs text-gray-400 mt-2">
                    <span class="mr-3">
                        <span class="font-medium">Type:</span> {{ ucfirst($project->projectType->display_name) }}
                    </span>
                    <span>
                        <span class="font-medium">Role:</span> {{ ucfirst($project->pivot->role) }}
                        @if($project->pivot->primary) (Primary) @endif
                    </span>
                </div>
            </div>
        </div>
        <div class="mod-card-stats flex flex-row flex-wrap lg:flex-col lg:justify-center shrink-0 gap-1 mt-2 lg:mt-0">
            <div>
                @svg('lucide-calendar', 'w-5 h-5 inline')
                {{ $project->created_at ? $project->created_at->diffForHumans() : 'Unknown' }}
            </div>
            <div>
                @svg('lucide-trash-2', 'w-5 h-5 inline')
                {{ $project->deleted_at->diffForHumans() }}
            </div>
        </div>
    </div>
</div>
