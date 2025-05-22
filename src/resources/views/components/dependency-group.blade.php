@props(['title', 'dependencies', 'project', 'version', 'badgeColor'])

<div class="mb-4">
    <h3 class="text-lg font-semibold mb-2">{{ $title }}</h3>
    <div class="dependencies-list space-y-2">
        @foreach($dependencies as $dependency)
            <x-dependency-item
                :dependency="$dependency"
                :project="$project"
                :version="$version"
                badge-color="{{ $badgeColor }}"
            />
        @endforeach
    </div>
</div>
