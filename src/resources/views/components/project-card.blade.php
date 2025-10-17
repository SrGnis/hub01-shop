@props(['project'])

<x-card class="!py-3 !px-5">
    <!-- Mobile Layout (default) -->
    <div class="block lg:hidden">
        <!-- Image with Title, Byline, and Description -->
        <div class="flex gap-4 mb-4">
            <div class="flex-shrink-0 flex items-center">
                <a href="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}"
                    class="block hover:opacity-80 transition-opacity">
                    <img src="{{ $project->getLogoUrl() ?? '/images/default-project.png' }}"
                            class="w-28 h-28 object-cover rounded-lg"
                            alt="{{ $project->name }} Logo">
                </a>
            </div>

            <div class="flex-grow min-w-0">
                <h3 class="text-xl font-bold mb-1">
                    <a href="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}"
                        class="text-primary hover:text-primary-focus transition-colors">
                        {{ $project->pretty_name ?? $project->name ?? 'Unnamed Project' }}
                    </a>
                </h3>
                <p class="text-sm mb-2">
                    by <span class="font-medium">{{ $project->owner->first() ? $project->owner->first()->name : 'Unknown' }}</span>
                </p>
                <!-- Description now appears next to the image -->
                <p class="text-sm leading-relaxed">
                    {{ $project->summary ?? 'No description available' }}
                </p>
            </div>
        </div>

        <!-- Tags -->
        @if($project->tags->count() > 0)
            <div class="mb-4">
                <div class="flex flex-wrap gap-2">
                    @foreach ($project->tags as $tag)
                        <div class="flex items-center text-xs bg-base-200 px-2 py-1 rounded">
                            <x-icon :name="$tag->icon" class="w-3 h-3 mr-1" />
                            {{ $tag->name }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Stats at bottom for mobile -->
        <div class="flex flex-wrap gap-3 text-xs border-t pt-3">
            <div class="flex items-center gap-1">
                <x-icon name="lucide-download" class="w-3 h-3" />
                <span>
                    {{ $project->downloads }} downloads
                </span>
            </div>

            @if($project->recent_release_date)
                <div class="flex items-center gap-1">
                    <x-icon name="lucide-calendar" class="w-3 h-3" />
                    <span>Updated {{ \Carbon\Carbon::parse($project->recent_release_date)->diffForHumans() }}</span>
                </div>
            @endif

            <div class="flex items-center gap-1">
                <x-icon name="lucide-calendar-plus" class="w-3 h-3" />
                <span>Created {{ $project->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>

    <!-- Desktop Layout (lg and up) -->
    <div class="hidden lg:block">
        <div class="flex gap-6">
            <!-- Left Column: Image -->
            <div class="flex-shrink-0 flex items-center">
                <a href="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}"
                    class="block hover:opacity-80 transition-opacity">
                    <img src="{{ $project->getLogoUrl() ?? '/images/default-project.png' }}"
                        class="w-32 h-32 object-cover rounded-lg"
                        alt="{{ $project->name }} Logo">
                </a>
            </div>

            <!-- Center Column: Content -->
            <div class="flex-grow min-w-0">
                <!-- Title and Byline -->
                <div class="mb-3">
                    <h3 class="text-xl font-bold mb-1">
                        <a href="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}"
                            class="text-primary hover:text-primary-focus transition-colors">
                            {{ $project->pretty_name ?? $project->name ?? 'Unnamed Project' }}
                        </a>
                    </h3>
                    <p class="">
                        by <span class="font-medium">{{ $project->owner->first() ? $project->owner->first()->name : 'Unknown' }}</span>
                    </p>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <p class="text-sm leading-relaxed">
                        {{ $project->summary ?? 'No description available' }}
                    </p>
                </div>

                <!-- Tags spanning full width at bottom -->
                @if($project->tags->count() > 0)
                    <div>
                        <div class="flex flex-wrap gap-4">
                            @foreach ($project->tags as $tag)
                                <div class="flex items-center text-xs bg-base-200 px-2 py-1 rounded">
                                    <x-icon :name="$tag->icon" class="w-3 h-3 mr-1" />
                                    {{ $tag->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Right Column: Stats -->
            <div class="flex-shrink-0 w-48 flex flex-col justify-evenly text-sm">
                <div class="flex items-center gap-2">
                    <x-icon name="lucide-download" />
                    <span><span class="font-bold text-xl">
                        {{ $project->downloads }} downloads
                    </span>
                </div>

                @if($project->recent_release_date)
                    <div class="flex items-center gap-2">
                        <x-icon name="lucide-calendar" />
                        <span>Updated {{ \Carbon\Carbon::parse($project->recent_release_date)->diffForHumans() }}</span>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <x-icon name="lucide-calendar-plus" />
                    <span>Created {{ $project->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>
    </div>
</x-card>
