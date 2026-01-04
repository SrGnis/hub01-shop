<div class="w-full m-auto py-6">
    <x-card>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Abuse Report Management</h1>
        </div>

        {{-- Search and Filters --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <x-input label="Search" placeholder="Search by reason, reporter name or email..."
                wire:model.live.debounce.300ms="search" icon="lucide-search" clearable />

            <x-select label="Filter by Status" wire:model.live="statusFilter" :options="[
                ['id' => '', 'name' => 'All'],
                ['id' => 'pending', 'name' => 'Pending'],
                ['id' => 'resolved', 'name' => 'Resolved'],
            ]" placeholder="All Statuses" />

            <x-select label="Filter by Type" wire:model.live="typeFilter" :options="[
                ['id' => '', 'name' => 'All Types'],
                ['id' => 'App\\Models\\Project', 'name' => 'Projects'],
                ['id' => 'App\\Models\\ProjectVersion', 'name' => 'Project Versions'],
                ['id' => 'App\\Models\\User', 'name' => 'Users'],
            ]" placeholder="All Types" />
        </div>

        {{-- Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <x-stat title="Total Reports" value="{{ App\Models\AbuseReport::count() }}"
                icon="lucide-flag" class="bg-info/10" />
            <x-stat title="Pending Reports" value="{{ App\Models\AbuseReport::pending()->count() }}"
                icon="lucide-clock" class="bg-warning/10" />
            <x-stat title="Resolved Reports" value="{{ App\Models\AbuseReport::resolved()->count() }}"
                icon="lucide-check-circle" class="bg-success/10" />
        </div>

        {{-- Reports Table --}}
        <x-table :headers="[
            ['key' => 'reporter.name', 'label' => 'Reporter'],
            ['key' => 'reason', 'label' => 'Reason'],
            ['key' => 'reportable_type', 'label' => 'Type'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'created_at', 'label' => 'Reported'],
            ['key' => 'actions', 'label' => 'Actions'],
        ]" :rows="$reports" :sort-by="$sortBy" with-pagination>
            @scope('cell_reporter.name', $report)
                @if ($report->reporter)
                    <div class="flex items-center gap-2">
                        <x-avatar placeholder="{{ strtoupper(substr($report->reporter->name, 0, 1)) }}"
                            placeholder-text-class="text-sm font-bold"
                            placeholder-bg-class="bg-primary text-primary-content"
                            class="!w-8" image="{{ $report->reporter->getAvatarUrl() }}" />
                        <div>
                            <div class="font-medium">{{ $report->reporter->name }}</div>
                            <div class="text-xs text-base-content/60">{{ $report->reporter->email }}</div>
                        </div>
                    </div>
                @else
                    <span class="text-base-content/40">Unknown</span>
                @endif
            @endscope

            @scope('cell_reason', $report)
                <div class="max-w-xs truncate" title="{{ $report->reason }}">
                    {{ $report->reason }}
                </div>
            @endscope

            @scope('cell_reportable_type', $report)
                @if ($report->reportable)
                    @php
                        $item = $report->reportable;
                        $type = class_basename($report->reportable_type);
                    @endphp
                    <div class="flex items-center gap-2">
                        @if ($type === 'Project')
                            <x-avatar placeholder="{{ strtoupper(substr($item->name, 0, 1)) }}"
                                placeholder-text-class="text-sm font-bold"
                                placeholder-bg-class="bg-secondary text-secondary-content"
                                class="!w-8" image="{{ $item->logo_path ? Storage::url($item->logo_path) : null }}" />
                            <div>
                                <div class="font-medium">{{ $item->name }}</div>
                                <code class="text-xs text-base-content/60">{{ $item->slug }}</code>
                            </div>
                        @elseif($type === 'ProjectVersion')
                            <x-avatar placeholder="V"
                                placeholder-text-class="text-sm font-bold"
                                placeholder-bg-class="bg-accent text-accent-content"
                                class="!w-8" />
                            <div>
                                <div class="font-medium">{{ $item->version }}</div>
                                <div class="text-xs text-base-content/60">{{ $item->project->name ?? 'Unknown Project' }}</div>
                            </div>
                        @elseif($type === 'User')
                            <x-avatar placeholder="{{ strtoupper(substr($item->name, 0, 1)) }}"
                                placeholder-text-class="text-sm font-bold"
                                placeholder-bg-class="bg-primary text-primary-content"
                                class="!w-8" image="{{ $item->getAvatarUrl() }}" />
                            <div>
                                <div class="font-medium">{{ $item->name }}</div>
                                <div class="text-xs text-base-content/60">{{ $item->email }}</div>
                            </div>
                        @else
                            <span class="text-base-content/60">{{ $type }}</span>
                        @endif
                    </div>
                @else
                    <span class="text-base-content/40">Item deleted</span>
                @endif
            @endscope


            @scope('cell_status', $report)
                @if ($report->isPending())
                    <x-badge value="Pending" class="badge-warning" />
                @else
                    <x-badge value="Resolved" class="badge-success" />
                @endif
            @endscope

            @scope('cell_created_at', $report)
                <div class="flex flex-col">
                    <span>{{ $report->created_at->format('M d, Y') }}</span>
                    <span class="text-xs text-base-content/60">{{ $report->created_at->diffForHumans() }}</span>
                </div>
            @endscope

            @scope('cell_actions', $report)
                <div class="flex gap-2">
                    @if ($report->isPending())
                        <x-button icon="lucide-check" wire:click="markAsResolved({{ $report->id }})"
                            class="btn-sm btn-success" tooltip="Mark as resolved"
                            wire:confirm="Are you sure you want to mark this report as resolved?" />
                    @else
                        <x-button icon="lucide-refresh-cw" wire:click="markAsPending({{ $report->id }})"
                            class="btn-sm btn-warning" tooltip="Reopen report"
                            wire:confirm="Are you sure you want to reopen this report?" />
                    @endif

                    <x-button icon="lucide-trash-2" wire:click="deleteReport({{ $report->id }})"
                        class="btn-sm btn-ghost text-error" tooltip="Delete report"
                        wire:confirm="Are you sure you want to delete this report?" />
                </div>
            @endscope
        </x-table>
    </x-card>
</div>
