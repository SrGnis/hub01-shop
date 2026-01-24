@props([
    'tagGroups' => null,
    'versionTagGroups' => null,
    'selectedTagsModel' => null,
    'selectedVersionTagsModel' => null,
    'releaseDatePeriodModel' => null,
    'releaseDatePeriod' => "all",
    'releaseDateStartModel' => null,
    'releaseDateEndModel' => null,
])

<div>
    {{-- Project Filters --}}
    @if ($tagGroups && $tagGroups->count() > 0 && $selectedTagsModel)
        <div class="mb-6" x-ref="projectTagsFilter">
            <div class="flex items-center justify-center">
                <h3 class="font-semibold text-lg mb-4">Project Filters</h3>
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

    {{-- Version Filters --}}
    @if ($versionTagGroups && $versionTagGroups->count() > 0 && $selectedVersionTagsModel)
        <div class="mb-6" x-ref="versionTagsFilter">
            <div class="flex items-center justify-center">
                <h3 class="font-semibold text-lg mb-4">Version Filters</h3>
            </div>

            {{-- Date Range Filter --}}
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-medium text-sm">Release Date</h4>
                    <span wire:loading wire:target="{{$releaseDatePeriodModel}},{{$releaseDateStartModel}},{{$releaseDateEndModel}}" class="loading loading-spinner w-4 h-4"></span>
                </div>

                {{-- Period Selector --}}
                <div class="mb-4">
                    <x-select
                        wire:model.live="{{$releaseDatePeriodModel}}"
                        :options="[
                            ['id' => 'all', 'name' => 'All Time'],
                            ['id' => 'last_30_days', 'name' => 'Last 30 Days'],
                            ['id' => 'last_90_days', 'name' => 'Last 90 Days'],
                            ['id' => 'last_year', 'name' => 'Last Year'],
                            ['id' => 'custom', 'name' => 'Custom Range']
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                </div>

                {{-- Custom Date Range Picker (shown when period is 'custom') --}}
                @if ($releaseDatePeriod === 'custom')
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <x-datetime
                                wire:model.live="{{$releaseDateStartModel}}"
                                label="Start Date"
                                icon="calendar"
                            />
                        </div>
                        <div>
                            <x-datetime
                                wire:model.live="{{$releaseDateEndModel}}"
                                label="End Date"
                                icon="calendar"
                            />
                        </div>
                    </div>
                @endif
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
