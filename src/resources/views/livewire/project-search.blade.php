<div x-data="{ showMobileFilters: false }">
    <!-- HEADER -->
    <x-header title="{{ $projectType->pluralizedDisplayName() }}" separator progress-indicator>
        <x-slot:actions>
            <x-input
                placeholder="Search {{ $projectType->pluralizedDisplayName() }}..."
                wire:model.live.debounce.500ms="search"
                clearable
                icon="search"
                class="w-full max-w-md"
            />
            <x-button
                label="Filters"
                @click="showMobileFilters = true"
                responsive
                icon="list-filter"
                class="btn-primary lg:hidden"
            />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- DESKTOP FILTERS SIDEBAR -->
        <div class="hidden lg:block lg:col-span-3">
            <x-card title="Filters" separator>
                <!-- Project Tags -->
                @if($this->tagGroups->count() > 0)
                    <div class="mb-6" x-ref="projectTagsFilter">
                        <div class="flex items-center justify-center">
                            <h3 class="font-semibold text-lg mb-4">Project Tags</h3>
                        </div>
                        @foreach ($this->tagGroups as $tagGroup)
                            <div class="mb-4">
                                <h4 class="font-medium text-sm mb-2">{{ $tagGroup->name }}</h4>
                                <div class="space-y-2 ml-4">
                                    @foreach ($tagGroup->tags as $tag)
                                        <x-checkbox
                                            wire:model.live="selectedTags"
                                            value="{{ $tag->id }}"
                                            right
                                            class="text-sm"
                                        >
                                            <x-slot:label class="flex items-center gap-2">
                                                <div class="flex items-center gap-2">
                                                    <x-icon :name="$tag->icon" class="w-4 h-4" />
                                                    {{ $tag->name }}
                                                </div>
                                            </x-slot:label>
                                        </x-checkbox>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Version Tags -->
                @if($this->versionTagGroups->count() > 0)
                    <div class="mb-6" x-ref="versionTagsFilter">
                        <div class="flex items-center justify-center">
                            <h3 class="font-semibold text-lg mb-4">Version Tags</h3>
                        </div>
                        @foreach ($this->versionTagGroups as $tagGroup)
                            <div class="mb-4">
                                <h4 class="font-medium text-sm mb-2">{{ $tagGroup->name }}</h4>
                                <div class="space-y-2 ml-4">
                                    @foreach ($tagGroup->tags as $tag)
                                        <x-checkbox
                                            wire:model.live="selectedVersionTags"
                                            value="{{ $tag->id }}"
                                            right
                                            class="text-sm"
                                        >
                                            <x-slot:label class="flex items-center gap-2">
                                                <div class="flex items-center gap-2">
                                                    <x-icon :name="$tag->icon" class="w-4 h-4" />
                                                    {{ $tag->name }}
                                                </div>
                                            </x-slot:label>
                                        </x-checkbox>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <!-- MAIN CONTENT -->
        <div class="lg:col-span-9">
            <!-- SORTING OPTIONS (Desktop) -->
            <div class="hidden lg:block mb-4">
                <x-card class="p-4">
                    <div class="flex flex-wrap gap-4 items-center justify-between" x-ref="sortingOptions">
                        <div class="flex flex-wrap gap-4 items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium">Order by</span>
                                <x-select
                                    wire:model.live="orderBy"
                                    :options="$this->orderOptions"
                                    option-value="id"
                                    option-label="name"
                                    class="w-48"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium">Direction</span>
                                <x-select
                                    wire:model.live="orderDirection"
                                    :options="$this->directionOptions"
                                    option-value="id"
                                    option-label="name"
                                    class="w-36"
                                />
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">Per page</span>
                            <x-select
                                wire:model.live="resultsPerPage"
                                :options="$this->perPageOptions"
                                option-value="id"
                                option-label="name"
                                class="w-24"
                            />
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- PROJECTS LIST -->
            <div class="space-y-4">
                <!-- Pagination Top -->
                {{ $this->projects->onEachSide(1)->links('vendor.livewire.tailwind') }}

                <!-- Project Cards -->
                @forelse ($this->projects as $project)
                    <x-project-card :project="$project" />
                @empty
                    <x-card class="text-center py-12">
                        <x-icon name="lucide-search" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium mb-2">No projects found</h3>
                        <p class="">Try adjusting your search criteria or filters.</p>
                    </x-card>
                @endforelse

                <!-- Pagination Bottom -->
                {{ $this->projects->onEachSide(1)->links('vendor.livewire.tailwind') }}
            </div>
        </div>
    </div>

    <!-- MOBILE FILTERS MODAL -->
    <x-mary-modal
        x-show="showMobileFilters"
        title="Filter & Sort Projects"
        separator
        box-class="max-h-[90vh]"
    >

        <div class="space-y-6">
            <!-- Sorting Options -->
            <div class="border-b pb-6">
                <h3 class="font-semibold text-lg mb-4">Sort Options</h3>
                <div class="space-y-4" x-html="$refs.sortingOptions ? $refs.sortingOptions.innerHTML : ''"></div>
            </div>

            <!-- Project Tags -->
            <div class="pb-6" x-show="$refs.projectTagsFilter" x-html="$refs.projectTagsFilter ? $refs.projectTagsFilter.innerHTML : ''"></div>

            <!-- Version Tags -->
            <div x-show="$refs.versionTagsFilter" x-html="$refs.versionTagsFilter ? $refs.versionTagsFilter.innerHTML : ''"></div>
        </div>

        <x-slot:actions>
            <x-button label="Clear All" wire:click="clearFilters" icon="x" />
            <x-button label="Apply Filters" @click="showMobileFilters = false" icon="check" class="btn-primary" />
        </x-slot:actions>
    </x-mary-modal>
</div>
