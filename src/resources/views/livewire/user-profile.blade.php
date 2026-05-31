<div class="container mx-auto px-4 py-8 max-w-7xl" x-data="{ showProjectFilters: false }">
    <livewire:report-abuse />

    <!-- User Profile Header -->
    <x-card class="mb-4">
        <div class="flex flex-col-reverse md:flex-row items-end md:items-center">
            <!-- User Avatar with Info -->
            <div class="w-full md:max-w-3xl">
                <x-avatar placeholder="{{ strtoupper(substr($user->name, 0, 1)) }}"
                    placeholder-text-class="text-3xl font-bold" placeholder-bg-class="bg-primary text-primary-content"
                    class="!w-24" image="{{ $user->getAvatarUrl() }}">
                    <x-slot:title class="text-3xl !font-bold pl-2">
                        {{ $user->name }}
                    </x-slot:title>

                    <x-slot:subtitle class="grid gap-1.5 mt-1.5 pl-2">
                        @if ($user->bio)
                            <p class="text-sm text-base-content/80">{{ $user->bio }}</p>
                        @endif

                        <div class="flex flex-wrap gap-3 text-xs">
                            <x-icon name="lucide-calendar"
                                label="Member since {{ $user->created_at->format('F Y') }}" />
                            <x-icon name="lucide-package"
                                label="{{ $this->ownedProjectsCount }} {{ Str::plural('project', $this->ownedProjectsCount) }} owned" />
                            <x-icon name="lucide-heart"
                                label="{{ number_format($this->aggregateFavorites) }} {{ Str::plural('favorite', $this->aggregateFavorites) }}" />
                            <x-icon name="lucide-download"
                                label="{{ number_format($this->aggregateDownloads) }} {{ Str::plural('download', $this->aggregateDownloads) }}" />
                        </div>
                    </x-slot:subtitle>
                </x-avatar>
            </div>

            <!-- User Actions -->
            <div class="flex-grow">
                <div class="flex justify-end">
                    @auth
                        @if (auth()->id() === $user->id)
                            <x-button link="{{ route('user.profile.edit') }}" icon="lucide-pencil" class="btn-primary">
                                Edit Profile
                            </x-button>
                        @endif
                    @endauth
                    <x-dropdown right>
                        <x-slot:trigger>
                            <x-button icon="ellipsis" class="btn-ghost" />
                        </x-slot:trigger>

                        <x-menu-item
                            title="Report"
                            class="text-error"
                            icon="flag"
                            @click="$dispatch('open-report-modal', { itemId: {{ $user->id }}, itemType: 'App\\\\Models\\\\User', itemName: '{{ addslashes($user->name) }}' })"
                        />
                    </x-dropdown>
                </div>
            </div>
        </div>
    </x-card>

    <x-tabs wire:model="activeTab" class="mb-6">
        <x-tab name="projects" label="Projects" icon="lucide-package">
            <div class="space-y-6 pt-4">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <div class="hidden lg:block lg:col-span-3">
                        <x-card>
                            <x-slot:title class="flex items-center justify-between gap-3">
                                <span>Filters</span>
                                <x-button label="Clear Filters" wire:click="clearProjectFilters" icon="x" class="btn-ghost" />
                            </x-slot:title>

                            <x-project-filters
                                :tag-groups="$this->tagGroups"
                                :version-tag-groups="$this->versionTagGroups"
                                selected-tags-model="selectedTags"
                                selected-version-tags-model="selectedVersionTags"
                                release-date-period-model="releaseDatePeriod"
                                :release-date-period="$releaseDatePeriod"
                                release-date-start-model="releaseDateStart"
                                release-date-end-model="releaseDateEnd"
                            />
                        </x-card>
                    </div>

                    <div class="lg:col-span-9 space-y-4">
                        <x-card class="p-4">
                            <div class="flex flex-row lg:flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                <x-input placeholder="Search projects..."
                                    wire:model.live.debounce.500ms="projectSearch" clearable icon="search" class="w-full xl:max-w-sm" />

                                <div class="hidden lg:flex flex-wrap items-center gap-3" x-ref="profileProjectSortingOptions">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium">Order by</span>
                                        <x-select wire:model.live="orderBy" :options="$this->orderOptions" option-value="id"
                                            option-label="name" class="w-44" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium">Direction</span>
                                        <x-select wire:model.live="orderDirection" :options="$this->directionOptions" option-value="id"
                                            option-label="name" class="w-36" />
                                    </div>
                                </div>

                                <x-button label="Filters" @click="showProjectFilters = true" responsive icon="list-filter"
                                    class="btn-primary lg:hidden" />
                            </div>
                        </x-card>

                        @if ($this->activeProjects->count() > 0)
                            <div class="space-y-4">
                                @foreach ($this->activeProjects as $project)
                                    <x-project-card :project="$project" />
                                @endforeach
                            </div>
                        @endif

                        @if ($this->activeProjects->count() === 0)
                            <x-card class="text-center py-8">
                                <x-icon name="lucide-package" class="w-16 h-16 mx-auto mb-4" />
                                <h3 class="text-lg font-medium mb-2">No projects found</h3>
                                <p class="text-base-content/60">Try adjusting the search or filters for this profile.</p>
                            </x-card>
                        @endif
                    </div>
                </div>
            </div>
        </x-tab>

        <x-tab name="collections" label="Collections" icon="lucide-folder-open">
            <div class="space-y-4 pt-4">
                @if (auth()->id() === $user->id && $this->favoritesCollection)
                    <x-collection-card :collection="$this->favoritesCollection" :entry-count="$this->favoritesCollection->entries_count" />
                @endif

                @forelse ($this->visibleCollections as $collection)
                    <x-collection-card :collection="$collection" :entry-count="$collection->entries_count" />
                @empty
                    <x-card class="text-center py-8">
                        <x-icon name="lucide-folder-open" class="w-16 h-16 mx-auto mb-4" />
                        <h3 class="text-lg font-medium mb-2">No collections yet</h3>
                        <p class="text-base-content/60">No collections are visible for this profile.</p>
                    </x-card>
                @endforelse
            </div>
        </x-tab>
    </x-tabs>

    <x-mary-modal x-show="showProjectFilters" title="Filter Projects" box-class="max-h-[90vh]"
        class="backdrop-blur-sm">
        <div class="space-y-6">
            <div class="border-b pb-6">
                <h3 class="font-semibold text-lg mb-4">Sort Options</h3>
                <div class="space-y-4" x-html="$refs.profileProjectSortingOptions ? $refs.profileProjectSortingOptions.innerHTML : ''"></div>
            </div>

            <x-project-filters
                :tag-groups="$this->tagGroups"
                :version-tag-groups="$this->versionTagGroups"
                selected-tags-model="selectedTags"
                selected-version-tags-model="selectedVersionTags"
                release-date-period-model="releaseDatePeriod"
                :release-date-period="$releaseDatePeriod"
                release-date-start-model="releaseDateStart"
                release-date-end-model="releaseDateEnd"
            />
        </div>

        <x-slot:actions>
            <x-button label="Clear All" wire:click="clearProjectFilters" icon="x" />
            <x-button label="Apply Filters" @click="showProjectFilters = false" icon="check" class="btn-primary" />
        </x-slot:actions>
    </x-mary-modal>

    <x-project-collection-modal
        wire:model="showCollectionModal"
        :target-project-name="$collectionTargetProjectName"
        :available-collections="$this->availableCollections"
    />
</div>
