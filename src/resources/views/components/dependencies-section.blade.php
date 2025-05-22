@props(['version', 'project'])

@if($version->dependencies->count() > 0)
<div class="bg-zinc-800 text-gray-300 p-4 mb-6 rounded-md">
    <h2 class="text-xl font-bold mb-4">Dependencies</h2>

    <!-- Required Dependencies -->
    @php $requiredDeps = $version->dependencies->where('dependency_type', 'required'); @endphp
    @if($requiredDeps->count() > 0)
        <x-dependency-group
            title="Required"
            :dependencies="$requiredDeps"
            :project="$project"
            :version="$version"
            badgeColor="bg-red-700"
        />
    @endif

    <!-- Optional Dependencies -->
    @php $optionalDeps = $version->dependencies->where('dependency_type', 'optional'); @endphp
    @if($optionalDeps->count() > 0)
        <x-dependency-group
            title="Optional"
            :dependencies="$optionalDeps"
            :project="$project"
            :version="$version"
            badgeColor="bg-blue-700"
        />
    @endif

    <!-- Embedded Dependencies -->
    @php $embeddedDeps = $version->dependencies->where('dependency_type', 'embedded'); @endphp
    @if($embeddedDeps->count() > 0)
        <x-dependency-group
            title="Embedded"
            :dependencies="$embeddedDeps"
            :project="$project"
            :version="$version"
            badgeColor="bg-green-700"
        />
    @endif
</div>
@endif
