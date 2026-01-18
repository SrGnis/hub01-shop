@props([
    'tagGroups' => null,
    'versionTagGroups' => null,
    'selectedTagsModel' => null,
    'selectedVersionTagsModel' => null,
])

<div>
    {{-- Project Tags --}}
    @if ($tagGroups && $tagGroups->count() > 0 && $selectedTagsModel)
        <div class="mb-6" x-ref="projectTagsFilter">
            <div class="flex items-center justify-center">
                <h3 class="font-semibold text-lg mb-4">Project Tags</h3>
            </div>
            @foreach ($tagGroups as $tagGroup)
                <div class="mb-4">
                    <h4 class="font-medium text-sm mb-2">{{ $tagGroup->name }}</h4>
                    <div class="space-y-2 ml-4">
                        @foreach ($tagGroup->tags as $tag)
                            <x-tag-check-item :tag="$tag" :model="$selectedTagsModel" :live="true" />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Version Tags --}}
    @if ($versionTagGroups && $versionTagGroups->count() > 0 && $selectedVersionTagsModel)
        <div class="mb-6" x-ref="versionTagsFilter">
            <div class="flex items-center justify-center">
                <h3 class="font-semibold text-lg mb-4">Version Tags</h3>
            </div>
            @foreach ($versionTagGroups as $tagGroup)
                <div class="mb-4">
                    <h4 class="font-medium text-sm mb-2">{{ $tagGroup->name }}</h4>
                    <div class="space-y-2 ml-4">
                        @foreach ($tagGroup->tags as $tag)
                            <x-tag-check-item :tag="$tag" :model="$selectedVersionTagsModel" :live="true" />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
