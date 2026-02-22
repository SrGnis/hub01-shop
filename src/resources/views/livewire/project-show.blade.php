<div>
    <livewire:report-abuse />

    <!-- Back Button and Actions -->
    <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
        <x-button link="{{ route('project-search', ['projectType' => $project->projectType]) }}" icon="arrow-left"
            label="Back to Projects" class="btn-ghost" no-wire-navigate />
        <div class="flex flex-col lg:flex-row gap-2">
            @auth
                @can('uploadVersion', $project)
                    <x-button
                        link="{{ route('project.version.create', ['projectType' => $project->projectType, 'project' => $this->project]) }}"
                        icon="upload" label="Upload Version" class="btn-success" no-wire-navigate />
                @endcan

                @can('update', $project)
                    <x-button
                        link="{{ route('project.edit', ['projectType' => $project->projectType, 'project' => $project]) }}"
                        icon="pencil" label="Edit Project" class="btn-primary" no-wire-navigate />
                @endcan
            @endauth
            <x-dropdown right>
                <x-slot:trigger>
                    <x-button icon="ellipsis" class="btn-ghost" />
                </x-slot:trigger>

                <x-menu-item title="Report" class="text-error" icon="flag"
                    @click="$dispatch('open-report-modal', { itemId: {{ $project->id }}, itemType: 'App\\\\Models\\\\Project', itemName: '{{ addslashes($project->name) }}' })" />
            </x-dropdown>
        </div>
    </div>

    {{-- Pending Approval Notice --}}
    @if ($project->isPending())
        <x-alert title="This project is under review"
            description="This project is currently pending admin approval. Only the owner can see it, and it cannot be edited until the review is complete."
            icon="clock" class="mb-6 alert-warning" />
    @endif

    {{-- Draft Project Notice --}}
    @if ($project->isDraft())
        <x-alert title="This project is a draft"
            description="This project is currently a draft. Only the owner can see it, you can submit it for review in the project settings."
            icon="clock" class="mb-6 alert-info" />
    @endif

    {{-- Rejected Project Notice --}}
    @if ($project->isRejected())
        <x-alert title="This project was rejected"
            description="This project was rejected by an admin. {{ $project->rejection_reason ?: 'Please contact an administrator for more information.' }}"
            icon="x-circle" class="mb-6 alert-error" />
    @endif

    {{-- Inactive Project Notice --}}
    @if ($project->status === 'inactive')
        <x-alert title="This project is currently inactive"
            description="This project has been marked as inactive by its maintainers. It may not be actively maintained or supported."
            icon="triangle-alert" class="mb-6 alert-warning" />
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column - Main Content -->
        <div class="lg:col-span-8">
            <!-- Project Card -->
            <div class="mb-6">
                <x-project-card :project="$project" />
            </div>

            <!-- Navigation Tabs -->
            <x-tabs wire:model="activeTab" class="mb-6">
                <x-tab name="description" label="Description">
                    <x-project-show-description :project="$project" />
                </x-tab>
                <x-tab name="versions" label="Versions">
                    <x-project-show-versions
                        :project="$project"
                        :versions="$versions"
                        :sort-by="$sortBy"
                        :version-tag-groups="$this->versionTagGroups"
                        :release-date-period="$this->releaseDatePeriod"
                    />
                </x-tab>
                <x-tab name="changelog" label="Changelog">
                    <x-project-show-changelog :versions="$changelogVersions" />
                </x-tab>
            </x-tabs>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Versions Section -->
            <x-project-recent-versions :project="$project" />

            <!-- Creators Section -->
            <x-project-creators :project="$project" />

            <!-- External Credits Section -->
            <x-project-external-credits :project="$project" />

            <!-- Links Section -->
            <x-project-links :project="$project" />
        </div>
    </div>
</div>
