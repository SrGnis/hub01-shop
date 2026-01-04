<div class="w-full m-auto py-6">
    <x-card>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Project Management</h1>
        </div>

        {{-- Search and Filters --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <x-input label="Search" placeholder="Search projects by name or slug..." wire:model.live.debounce.300ms="search"
                icon="lucide-search" clearable />

            <x-select label="Filter by Type" wire:model.live="filterType" :options="$projectTypes
                ->map(fn($type) => ['id' => $type->value, 'name' => $type->display_name])
                ->prepend(['id' => '', 'name' => 'All Types'])" placeholder="All Types" />

            <x-select label="Filter by Status" wire:model.live="filterStatus" :options="[
                ['id' => '', 'name' => 'All'],
                ['id' => 'active', 'name' => 'Active'],
                ['id' => 'inactive', 'name' => 'Inactive'],
                ['id' => 'deactivated', 'name' => 'Deactivated'],
                ['id' => 'deleted', 'name' => 'Deleted'],
            ]"
                placeholder="All Statuses" />
        </div>

        {{-- Projects Table --}}
        <x-table :headers="[
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'slug', 'label' => 'Slug'],
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'size', 'label' => 'Size'],
            ['key' => 'created_at', 'label' => 'Created'],
            ['key' => 'actions', 'label' => 'Actions'],
        ]" :rows="$projects" :sort-by="$sortBy" with-pagination>
            @scope('cell_name', $project)
                <div class="flex items-center gap-2">
                    <x-avatar placeholder="{{ strtoupper(substr($project->name, 0, 1)) }}"
                        placeholder-text-class="text-sm font-bold"
                        placeholder-bg-class="bg-secondary text-secondary-content" class="!w-8"
                        image="{{ $project->logo_path ? Storage::url($project->logo_path) : null }}" />
                    <span class="font-medium">{{ $project->name }}</span>
                </div>
            @endscope

            @scope('cell_slug', $project)
                <code class="text-xs">{{ $project->slug }}</code>
            @endscope

            @scope('cell_type', $project)
                <x-badge :value="$project->projectType->display_name" class="badge-ghost" />
            @endscope

            @scope('cell_status', $project)
                @if ($project->trashed())
                    <x-badge value="Deleted" class="badge-error" />
                @elseif($project->isDeactivated())
                    <x-badge value="Deactivated" class="badge-error" />
                @elseif($project->status === 'active')
                    <x-badge value="Active" class="badge-success" />
                @else
                    <x-badge value="Inactive" class="badge-warning" />
                @endif
            @endscope

            @scope('cell_size', $project)
                {{ $project->formatted_size }}
            @endscope

            @scope('cell_created_at', $project)
                {{ $project->created_at->diffForHumans() }}
            @endscope

            @scope('cell_actions', $project)
                <div class="flex gap-2">
                    @if ($project->trashed())
                        <x-button icon="lucide-refresh-cw" wire:click="restoreProject({{ $project->id }})"
                            class="btn-sm btn-success" tooltip="Restore project" />
                    @else
                        <x-button icon="lucide-eye"
                            link="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}"
                            class="btn-sm btn-ghost" tooltip="View project" />
                        @if (!$project->isDeactivated())
                            <x-button icon="lucide-pencil"
                                link="{{ route('project.edit', ['projectType' => $project->projectType, 'project' => $project]) }}"
                                class="btn-sm btn-ghost" tooltip="Edit project" />
                        @endif
                        @if ($project->isDeactivated())
                            <x-button icon="lucide-check-circle" wire:click="reactivateProject({{ $project->id }})"
                                class="btn-sm btn-ghost text-success" tooltip="Reactivate project"
                                wire:confirm="Are you sure you want to reactivate this project?" />
                        @else
                            <x-button icon="lucide-ban" wire:click="deactivateProject({{ $project->id }})"
                                class="btn-sm btn-ghost text-warning" tooltip="Deactivate project"
                                wire:confirm="Are you sure you want to deactivate this project? It will be hidden from search and cannot be edited." />
                        @endif
                        <x-button icon="lucide-trash-2" wire:click="confirmProjectDeletion({{ $project->id }})"
                            class="btn-sm btn-ghost text-error" tooltip="Delete project" />
                    @endif
                </div>
            @endscope
        </x-table>
    </x-card>

    {{-- Delete Confirmation Modal --}}
    <x-modal wire:model="confirmingProjectDeletion" title="Delete Project">
        @if ($projectToDelete)
            @php
                $project = \App\Models\Project::withTrashed()->find($projectToDelete);
            @endphp
            @if ($project)
                <p>Are you sure you want to delete <strong>{{ $project->name }}</strong>?</p>
                <p class="text-sm text-gray-400 mt-2">This project will be soft-deleted and can be restored for 14 days.
                </p>
            @endif
        @endif

        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('confirmingProjectDeletion', false)" />
            <x-button label="Delete" wire:click="deleteProject" class="btn-error" />
        </x-slot:actions>
    </x-modal>
</div>
