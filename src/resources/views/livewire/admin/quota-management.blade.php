<div class="w-full m-auto py-6">
    <x-card>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Quota Management</h1>
        </div>

        {{-- Global Defaults Info --}}
        <div class="my-6 p-4 bg-base-200 rounded-lg">
            <h3 class="font-semibold mb-2">Global Default Quotas</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <div class="text-base-content/60">Total Storage</div>
                    <div class="font-medium">{{ number_format(config('quotas.total_storage_max') / 1073741824, 1) }} GB
                    </div>
                </div>
                <div>
                    <div class="text-base-content/60">Project Storage</div>
                    <div class="font-medium">{{ number_format(config('quotas.project_storage_max') / 1048576, 0) }} MB
                    </div>
                </div>
                <div>
                    <div class="text-base-content/60">Versions/Day</div>
                    <div class="font-medium">{{ config('quotas.versions_per_day_max') }}</div>
                </div>
                <div>
                    <div class="text-base-content/60">Pending Projects</div>
                    <div class="font-medium">{{ config('quotas.pending_projects_max') }}</div>
                </div>
            </div>
            <p class="text-xs text-base-content/60 mt-3">
                These defaults can be changed in <code class="bg-base-300 px-1 rounded">config/quotas.php</code> or via
                environment variables.
            </p>
        </div>

        <x-tabs wire:model="activeTab">
            {{-- Project Type Quotas --}}
            <x-tab name="project-types" label="Project Types" icon="lucide-package">
                <div class="py-4">
                    <p class="text-sm text-base-content/60 mb-4">
                        Set quota overrides for specific project types. Leave fields empty to use global defaults.
                    </p>

                    {{-- Search --}}
                    <div class="mb-4">
                        <x-input placeholder="Search project types..."
                            wire:model.live.debounce.300ms="searchProjectTypes" icon="lucide-search" clearable />
                    </div>

                    <x-table :headers="[
                        ['key' => 'display_name', 'label' => 'Project Type'],
                        ['key' => 'project_storage_max', 'label' => 'Project Storage Max', 'sortable' => false],
                        ['key' => 'versions_per_day_max', 'label' => 'Versions/Day', 'sortable' => false],
                        ['key' => 'version_size_max', 'label' => 'Version Size Max', 'sortable' => false],
                        ['key' => 'files_per_version_max', 'label' => 'Files/Version', 'sortable' => false],
                        ['key' => 'file_size_max', 'label' => 'File Size Max', 'sortable' => false],
                        ['key' => 'actions', 'label' => 'Actions'],
                    ]" :rows="$projectTypes" :sort-by="$sortBy" with-pagination>
                        @scope('cell_display_name', $projectType)
                            <div class="flex items-center gap-2">
                                <x-icon :name="$projectType->icon" class="w-5 h-5" />
                                <span class="font-medium">{{ $projectType->display_name }}</span>
                            </div>
                        @endscope

                        @scope('cell_project_storage_max', $projectType)
                            {{ $projectType->quota?->project_storage_max ? number_format($projectType->quota->project_storage_max / 1048576, 0) . ' MB' : '-' }}
                        @endscope

                        @scope('cell_versions_per_day_max', $projectType)
                            {{ $projectType->quota?->versions_per_day_max ?? '-' }}
                        @endscope

                        @scope('cell_version_size_max', $projectType)
                            {{ $projectType->quota?->version_size_max ? number_format($projectType->quota->version_size_max / 1048576, 0) . ' MB' : '-' }}
                        @endscope

                        @scope('cell_files_per_version_max', $projectType)
                            {{ $projectType->quota?->files_per_version_max ?? '-' }}
                        @endscope

                        @scope('cell_file_size_max', $projectType)
                            {{ $projectType->quota?->file_size_max ? number_format($projectType->quota->file_size_max / 1048576, 0) . ' MB' : '-' }}
                        @endscope

                        @scope('cell_actions', $projectType)
                            <x-button icon="lucide-pencil" wire:click="editProjectTypeQuota({{ $projectType->id }})"
                                class="btn-sm btn-ghost" tooltip="Edit quotas" />
                        @endscope
                    </x-table>
                </div>
            </x-tab>

            {{-- Project Quotas --}}
            <x-tab name="projects" label="Projects" icon="lucide-folder">
                <div class="py-4">
                    <p class="text-sm text-base-content/60 mb-4">
                        Set quota overrides for individual projects. Leave fields empty to use project type or global
                        defaults.
                    </p>

                    {{-- Search and Filters --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <x-input placeholder="Search projects by name or slug..."
                            wire:model.live.debounce.300ms="searchProjects" icon="lucide-search" clearable />

                        <x-select label="" wire:model.live="filterProjectType" :options="$allProjectTypes
                            ->map(fn($type) => ['id' => $type->id, 'name' => $type->display_name])
                            ->prepend(['id' => '', 'name' => 'All Types'])"
                            placeholder="Filter by Type" />
                        placeholder="Filter by Type" />
                    </div>

                    <x-table :headers="[
                        ['key' => 'name', 'label' => 'Project'],
                        ['key' => 'type', 'label' => 'Type'],
                        ['key' => 'owner', 'label' => 'Owner', 'sortable' => false],
                        ['key' => 'storage_used', 'label' => 'Storage Used', 'sortable' => false],
                        ['key' => 'project_storage_max', 'label' => 'Storage Limit', 'sortable' => false],
                        ['key' => 'versions_per_day_max', 'label' => 'Versions/Day', 'sortable' => false],
                        ['key' => 'version_size_max', 'label' => 'Version Size', 'sortable' => false],
                        ['key' => 'files_per_version_max', 'label' => 'Files/Version', 'sortable' => false],
                        ['key' => 'file_size_max', 'label' => 'File Size', 'sortable' => false],
                        ['key' => 'actions', 'label' => 'Actions'],
                    ]" :rows="$projects" :sort-by="$sortBy" with-pagination>
                        @scope('cell_name', $project)
                            <div class="flex items-center gap-2">
                                <x-avatar placeholder="{{ strtoupper(substr($project->name, 0, 1)) }}" class="!w-8"
                                    image="{{ $project->logo_path ? Storage::url($project->logo_path) : null }}" />
                                <span>{{ $project->name }}</span>
                            </div>
                        @endscope

                        @scope('cell_type', $project)
                            <x-badge :value="$project->projectType->display_name" class="badge-ghost" />
                        @endscope

                        @scope('cell_owner', $project)
                            @php $owner = $project->owner->first(); @endphp
                            {{ $owner?->name ?? 'Unknown' }}
                        @endscope

                        @scope('cell_storage_used', $project)
                            {{ number_format($project->storage_used / 1048576, 2) }} MB
                        @endscope

                        @scope('cell_project_storage_max', $project)
                            {{ $project->quota?->project_storage_max ? number_format($project->quota->project_storage_max / 1048576, 0) . ' MB' : '-' }}
                        @endscope

                        @scope('cell_versions_per_day_max', $project)
                            {{ $project->quota?->versions_per_day_max ?? '-' }}
                        @endscope

                        @scope('cell_version_size_max', $project)
                            {{ $project->quota?->version_size_max ? number_format($project->quota->version_size_max / 1048576, 0) . ' MB' : '-' }}
                        @endscope

                        @scope('cell_files_per_version_max', $project)
                            {{ $project->quota?->files_per_version_max ?? '-' }}
                        @endscope

                        @scope('cell_file_size_max', $project)
                            {{ $project->quota?->file_size_max ? number_format($project->quota->file_size_max / 1048576, 0) . ' MB' : '-' }}
                        @endscope

                        @scope('cell_actions', $project)
                            <x-button icon="lucide-pencil" wire:click="editProjectQuota({{ $project->id }})"
                                class="btn-sm btn-ghost" tooltip="Edit quotas" />
                        @endscope
                    </x-table>
                </div>
            </x-tab>

            {{-- User Quotas --}}
            <x-tab name="users" label="Users" icon="lucide-users">
                <div class="py-4">
                    <p class="text-sm text-base-content/60 mb-4">
                        Set total storage quota overrides for individual users. Leave empty to use global default (1GB).
                    </p>

                    {{-- Search and Filters --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <x-input placeholder="Search users by name or email..."
                            wire:model.live.debounce.300ms="searchUsers" icon="lucide-search" clearable />

                        <x-select label="" wire:model.live="filterRole" :options="[
                            ['id' => '', 'name' => 'All Roles'],
                            ['id' => 'user', 'name' => 'User'],
                            ['id' => 'admin', 'name' => 'Admin'],
                        ]"
                            placeholder="Filter by Role" />
                    </div>

                    <x-table :headers="[
                        ['key' => 'name', 'label' => 'User'],
                        ['key' => 'email', 'label' => 'Email'],
                        ['key' => 'role', 'label' => 'Role'],
                        ['key' => 'storage_used', 'label' => 'Storage Used', 'sortable' => false],
                        ['key' => 'total_storage_max', 'label' => 'Storage Limit', 'sortable' => false],
                        ['key' => 'actions', 'label' => 'Actions'],
                    ]" :rows="$users" :sort-by="$sortBy" with-pagination>
                        @scope('cell_name', $user)
                            <div class="flex items-center gap-2">
                                <x-avatar placeholder="{{ strtoupper(substr($user->name, 0, 1)) }}" class="!w-8"
                                    image="{{ $user->getAvatarUrl() }}" />
                                <span>{{ $user->name }}</span>
                            </div>
                        @endscope

                        @scope('cell_email', $user)
                            {{ $user->email }}
                        @endscope

                        @scope('cell_role', $user)
                            <x-badge :value="ucfirst($user->role)" :class="$user->isAdmin() ? 'badge-primary' : 'badge-ghost'" />
                        @endscope

                        @scope('cell_storage_used', $user)
                            {{ number_format($user->storage_used / 1048576, 2) }} MB
                        @endscope

                        @scope('cell_total_storage_max', $user)
                            {{ $user->quota?->total_storage_max ? number_format($user->quota->total_storage_max / 1048576, 0) . ' MB' : '-' }}
                        @endscope

                        @scope('cell_actions', $user)
                            <x-button icon="lucide-pencil" wire:click="editUserQuota({{ $user->id }})"
                                class="btn-sm btn-ghost" tooltip="Edit quota" />
                        @endscope
                    </x-table>
                </div>
            </x-tab>
        </x-tabs>
    </x-card>

    {{-- Project Type Quota Edit Modal --}}
    <x-modal wire:model="showProjectTypeModal" title="Edit Project Type Quotas"
        subtitle="Set quota overrides for this project type">
        <x-form wire:submit="saveProjectTypeQuota">
            <x-input label="Project Storage Max (MB)" wire:model="quotaForm.project_storage_max" type="number"
                hint="Leave empty to use global default" />
            <x-input label="Versions per Day Max" wire:model="quotaForm.versions_per_day_max" type="number"
                hint="Leave empty to use global default" />
            <x-input label="Version Size Max (MB)" wire:model="quotaForm.version_size_max" type="number"
                hint="Leave empty to use global default" />
            <x-input label="Files per Version Max" wire:model="quotaForm.files_per_version_max" type="number"
                hint="Leave empty to use global default" />
            <x-input label="File Size Max (MB)" wire:model="quotaForm.file_size_max" type="number"
                hint="Leave empty to use global default" />

            <x-slot:actions>
                <x-button label="Cancel" wire:click="cancelEdit" />
                <x-button label="Save" type="submit" class="btn-primary" spinner />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- Project Quota Edit Modal --}}
    <x-modal wire:model="showProjectModal" title="Edit Project Quotas"
        subtitle="Set quota overrides for this project">
        <x-form wire:submit="saveProjectQuota">
            <x-input label="Project Storage Max (MB)" wire:model="quotaForm.project_storage_max" type="number"
                hint="Leave empty to use project type or global default" />
            <x-input label="Versions per Day Max" wire:model="quotaForm.versions_per_day_max" type="number"
                hint="Leave empty to use project type or global default" />
            <x-input label="Version Size Max (MB)" wire:model="quotaForm.version_size_max" type="number"
                hint="Leave empty to use project type or global default" />
            <x-input label="Files per Version Max" wire:model="quotaForm.files_per_version_max" type="number"
                hint="Leave empty to use project type or global default" />
            <x-input label="File Size Max (MB)" wire:model="quotaForm.file_size_max" type="number"
                hint="Leave empty to use project type or global default" />

            <x-slot:actions>
                <x-button label="Cancel" wire:click="cancelEdit" />
                <x-button label="Save" type="submit" class="btn-primary" spinner />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- User Quota Edit Modal --}}
    <x-modal wire:model="showUserModal" title="Edit User Quota"
        subtitle="Set total storage quota override for this user">
        <x-form wire:submit="saveUserQuota">
            <x-input label="Total Storage Max (MB)" wire:model="quotaForm.total_storage_max" type="number"
                hint="Leave empty to use global default (1GB)" />

            <x-slot:actions>
                <x-button label="Cancel" wire:click="cancelEdit" />
                <x-button label="Save" type="submit" class="btn-primary" spinner />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
