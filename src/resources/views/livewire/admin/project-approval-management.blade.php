<div class="w-full lg:w-10/12 m-auto py-6">
    <x-card>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Project Approval Management</h1>
        </div>

        {{-- Search and Filters --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <x-input label="Search" placeholder="Search projects by name or slug..."
                wire:model.live.debounce.300ms="search" icon="lucide-search" clearable />

            <x-select label="Filter by Type" wire:model.live="filterType" :options="$projectTypes
                ->map(fn($type) => ['id' => $type->value, 'name' => $type->display_name])
                ->prepend(['id' => '', 'name' => 'All Types'])" placeholder="All Types" />

            <x-select label="Filter by Status" wire:model.live="filterStatus" :options="[
                ['id' => 'pending', 'name' => 'Pending'],
                ['id' => 'approved', 'name' => 'Approved'],
                ['id' => 'rejected', 'name' => 'Rejected'],
                ['id' => '', 'name' => 'All'],
            ]"
                placeholder="All Statuses" />

            <x-select label="Items per page" wire:model.live="perPage" :options="[['id' => 10, 'name' => '10'], ['id' => 25, 'name' => '25'], ['id' => 50, 'name' => '50']]" />
        </div>

        {{-- Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <x-stat title="Pending Projects" value="{{ App\Models\Project::withoutGlobalScopes()->pending()->count() }}"
                icon="lucide-clock" class="bg-warning/10" />
            <x-stat title="Approved (Last 7 Days)"
                value="{{ App\Models\Project::withoutGlobalScopes()->approved()->where('reviewed_at', '>=', now()->subDays(7))->count() }}"
                icon="lucide-check-circle" class="bg-success/10" />
            <x-stat title="Rejected (Last 7 Days)"
                value="{{ App\Models\Project::withoutGlobalScopes()->rejected()->where('reviewed_at', '>=', now()->subDays(7))->count() }}"
                icon="lucide-x-circle" class="bg-error/10" />
        </div>

        {{-- Projects Table --}}
        <x-table :headers="[
            ['key' => 'name', 'label' => 'Project'],
            ['key' => 'owner', 'label' => 'Owner'],
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'submitted_at', 'label' => 'Submitted'],
            ['key' => 'approval_status', 'label' => 'Status'],
            ['key' => 'actions', 'label' => 'Actions'],
        ]" :rows="$projects" :sort-by="$sortBy" with-pagination>
            @scope('cell_name', $project)
                <div class="flex items-center gap-2">
                    <x-avatar placeholder="{{ strtoupper(substr($project->name, 0, 1)) }}"
                        placeholder-text-class="text-sm font-bold"
                        placeholder-bg-class="bg-secondary text-secondary-content" class="!w-8"
                        image="{{ $project->logo_path ? Storage::url($project->logo_path) : null }}" />
                    <div>
                        <div class="font-medium">{{ $project->name }}</div>
                        <code class="text-xs text-base-content/60">{{ $project->slug }}</code>
                    </div>
                </div>
            @endscope

            @scope('cell_owner', $project)
                @php
                    $owner = $project->owner->first();
                @endphp
                @if ($owner)
                    <a href="{{ route('user.profile', $owner) }}" class="link link-hover">
                        {{ $owner->name }}
                    </a>
                @else
                    <span class="text-base-content/40">No owner</span>
                @endif
            @endscope

            @scope('cell_type', $project)
                <x-badge :value="$project->projectType->display_name" class="badge-ghost" />
            @endscope

            @scope('cell_submitted_at', $project)
                @if ($project->submitted_at)
                    <div class="flex flex-col">
                        <span>{{ $project->submitted_at->format('M d, Y') }}</span>
                        <span class="text-xs text-base-content/60">{{ $project->submitted_at->diffForHumans() }}</span>
                    </div>
                @else
                    <span class="text-base-content/40">N/A</span>
                @endif
            @endscope

            @scope('cell_approval_status', $project)
                @php
                    $status = $project->approval_status;
                @endphp
                <div class="flex items-center gap-2">
                    <x-icon :name="$status->icon()" class="w-4 h-4" />
                    <x-badge :value="$status->label()" class="badge-{{ $status->color() }}" />
                </div>
                @if ($project->isRejected() && $project->rejection_reason)
                    <div class="text-xs text-base-content/60 mt-1 max-w-xs truncate"
                        title="{{ $project->rejection_reason }}">
                        {{ $project->rejection_reason }}
                    </div>
                @endif
            @endscope

            @scope('cell_actions', $project)
                <div class="flex gap-2">
                    <x-button icon="lucide-eye"
                        link="{{ route('project.show', ['projectType' => $project->projectType, 'project' => $project]) }}"
                        class="btn-sm btn-ghost" tooltip="View project" />

                    @if ($project->isPending())
                        <x-button icon="lucide-check" wire:click="approveProject({{ $project->id }})"
                            class="btn-sm btn-success" tooltip="Approve project"
                            wire:confirm="Are you sure you want to approve this project?" />
                        <x-button icon="lucide-x" wire:click="confirmProjectRejection({{ $project->id }})"
                            class="btn-sm btn-error" tooltip="Reject project" />
                    @endif
                </div>
            @endscope
        </x-table>
    </x-card>

    {{-- Rejection Modal --}}
    <x-modal wire:model="confirmingProjectRejection" title="Reject Project">
        @if ($projectToReject)
            @php
                $project = \App\Models\Project::withoutGlobalScopes()->find($projectToReject);
            @endphp
            @if ($project)
                <p class="mb-4">You are about to reject <strong>{{ $project->name }}</strong>.</p>

                <x-textarea label="Rejection Reason" wire:model="rejectionReason"
                    placeholder="Please explain why this project is being rejected..." rows="4" required
                    hint="The project owner will receive this message and can resubmit after making changes." />

                @error('rejectionReason')
                    <div class="text-error text-sm mt-1">{{ $message }}</div>
                @enderror
            @endif
        @endif

        <x-slot:actions>
            <x-button label="Cancel" wire:click="$set('confirmingProjectRejection', false)" />
            <x-button label="Reject Project" wire:click="rejectProject" class="btn-error" />
        </x-slot:actions>
    </x-modal>
</div>
