<div>

    <!-- Back Button and Actions -->
    <div class="mb-6 flex justify-between items-center flex-wrap gap-4">
        <x-button
            link="{{ route('project-search', ['projectType' => $project->projectType]) }}"
            icon="arrow-left"
            label="Back to Projects"
            class="btn-ghost"
        />
        @auth
            <div class="flex flex-col lg:flex-row gap-2">
                @can('uploadVersion', $project)
                    <x-button
                        link="{{ route('project.version.create', ['projectType' => $project->projectType, 'project' => $this->project]) }}"
                        icon="upload"
                        label="Upload Version"
                        class="btn-success"
                    />
                @endcan

                @can('update', $project)
                    <x-button
                        link="{{ route('project.edit', ['projectType' => $project->projectType, 'project' => $project]) }}"
                        icon="pencil"
                        label="Edit Project"
                        class="btn-primary"
                    />
                @endcan
            </div>
        @endauth
    </div>

    <!-- Inactive Project Notice -->
    @if($project->status === 'inactive')
        <x-alert
            title="This project is currently inactive"
            description="This project has been marked as inactive by its maintainers. It may not be actively maintained or supported."
            icon="exclamation-triangle"
            class="mb-6 alert-warning"
        />
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
                    <x-project-show-versions :project="$project" :versions="$versions" :sort-by="$sortBy" />
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

            <!-- Links Section -->
            <x-project-links :project="$project" />
        </div>
    </div>
</div>

