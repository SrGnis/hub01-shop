<div>
    <livewire:report-abuse />

    {{-- Status Alerts --}}
    @if ($project->isPending())
        <x-alert title="This project is under review"
            description="This project is currently pending admin approval. Only the owner can see it, and it cannot be edited until the review is complete."
            icon="clock" class="mb-4 alert-warning" />
    @endif

    @if ($project->isDraft())
        <x-alert title="This project is a draft"
            description="This project is currently a draft. Only the owner can see it, you can submit it for review in the project settings."
            icon="clock" class="mb-4 alert-info" />
    @endif

    @if ($project->isRejected())
        <x-alert title="This project was rejected"
            description="This project was rejected by an admin. {{ $project->rejection_reason ?: 'Please contact an administrator for more information.' }}"
            icon="x-circle" class="mb-4 alert-error" />
    @endif

    @if ($project->status === 'inactive')
        <x-alert title="This project is currently inactive"
            description="This project has been marked as inactive by its maintainers. It may not be actively maintained or supported."
            icon="triangle-alert" class="mb-4 alert-warning" />
    @endif

    {{-- Top Action Bar --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        {{-- Back button --}}
        <x-button link="{{ route('project-search', ['projectType' => $project->projectType]) }}"
            icon="arrow-left"
            label="Back to Projects"
            class="btn-ghost btn-sm"
            no-wire-navigate />

        {{-- Action buttons --}}
        <div class="flex items-center gap-2">
            @auth
                @can('uploadVersion', $project)
                    <x-button
                        link="{{ route('project.version.create', ['projectType' => $project->projectType, 'project' => $this->project]) }}"
                        icon="upload"
                        label="Upload Version"
                        class="btn-success btn-sm hidden sm:inline-flex"
                        no-wire-navigate />
                    {{-- Mobile: icon-only upload --}}
                    <x-button
                        link="{{ route('project.version.create', ['projectType' => $project->projectType, 'project' => $this->project]) }}"
                        icon="upload"
                        class="btn-success btn-sm btn-square sm:hidden"
                        no-wire-navigate />
                @endcan

                @can('update', $project)
                    <x-button
                        link="{{ route('project.edit', ['projectType' => $project->projectType, 'project' => $project]) }}"
                        icon="pencil"
                        label="Edit Project"
                        class="btn-primary btn-sm hidden sm:inline-flex"
                        no-wire-navigate />
                    {{-- Mobile: icon-only edit --}}
                    <x-button
                        link="{{ route('project.edit', ['projectType' => $project->projectType, 'project' => $project]) }}"
                        icon="pencil"
                        class="btn-primary btn-sm btn-square sm:hidden"
                        no-wire-navigate />
                @endcan

                {{-- Favorite --}}
                <x-button
                    icon="heart"
                    class="btn-ghost btn-sm btn-square [&_svg]:w-5 [&_svg]:h-5 {{ (bool) ($project->is_favorited ?? false) ? 'text-secondary' : '' }}"
                    wire:click="toggleFavorite({{ $project->id }})"
                    title="{{ (bool) ($project->is_favorited ?? false) ? 'Remove from favorites' : 'Add to favorites' }}"
                />

                {{-- Bookmark --}}
                <x-button
                    icon="bookmark"
                    class="btn-ghost btn-sm btn-square [&_svg]:w-5 [&_svg]:h-5 {{ $this->isInUserCollection ? 'text-info' : '' }}"
                    wire:click="openAddToCollectionModal({{ $project->id }})"
                    title="Add to collection"
                />
            @endauth

            {{-- More menu --}}
            <x-dropdown right>
                <x-slot:trigger>
                    <x-button icon="ellipsis" class="btn-ghost btn-sm btn-square" />
                </x-slot:trigger>
                <x-menu-item title="Report" class="text-error" icon="flag"
                    @click="$dispatch('open-report-modal', { itemId: {{ $project->id }}, itemType: 'App\\\\Models\\\\Project', itemName: '{{ addslashes($project->name) }}' })" />
            </x-dropdown>
        </div>
    </div>

    {{-- Main Layout: stacked on mobile, 2-col on lg --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6">

        {{-- Left Column --}}
        <div class="lg:col-span-8 flex flex-col gap-4">

            {{-- Project Card --}}
            <x-project-card :project="$project" />

            {{-- Navigation Tabs --}}
            <div class="w-full">
                <x-tabs wire:model="activeTab" class="mb-0">
                    <x-tab name="description" label="Description">
                        <div class="pt-4">
                            <x-project-show-description :project="$project" />
                        </div>
                    </x-tab>
                    <x-tab name="versions" label="Versions">
                        <div class="pt-4">
                            <x-project-show-versions
                                :project="$project"
                                :versions="$versions"
                                :sort-by="$sortBy"
                                :version-tag-groups="$this->versionTagGroups"
                                :release-date-period="$this->releaseDatePeriod"
                            />
                        </div>
                    </x-tab>
                    <x-tab name="changelog" label="Changelog">
                        <div class="pt-4">
                            <x-project-show-changelog :versions="$changelogVersions" />
                        </div>
                    </x-tab>
                </x-tabs>
            </div>
        </div>

        {{-- Right Column / Sidebar --}}
        {{-- On mobile: shown after main content. On lg: side column. --}}
        <div class="lg:col-span-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">
            <x-project-recent-versions :project="$project" />
            <x-project-creators :project="$project" />
            <x-project-external-credits :project="$project" />
            <x-project-links :project="$project" />
        </div>
    </div>

    <x-project-collection-modal
        wire:model="showCollectionModal"
        :target-project-name="$collectionTargetProjectName"
        :available-collections="$this->availableCollections"
    />
</div>
