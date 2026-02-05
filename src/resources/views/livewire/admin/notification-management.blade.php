<div class="w-full m-auto py-6">
    <x-card>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Notification Management</h1>
        </div>

        {{-- Search and Filters --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <x-input label="Search User" placeholder="Search by name or email..."
                wire:model.live.debounce.300ms="search" icon="lucide-search" clearable />

            <x-input label="Date From" type="date" wire:model.live="dateFrom" />

            <x-input label="Date To" type="date" wire:model.live="dateTo" />

            <x-select label="Filter by Type" wire:model.live="typeFilter" :options="array_merge(
                [['id' => '', 'name' => 'All Types']],
                array_map(fn($name, $id) => ['id' => $id, 'name' => $name], $notificationTypes, array_keys($notificationTypes))
            )" placeholder="All Types" />
        </div>

        {{-- Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <x-stat title="Total Notifications" value="{{ \Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_type', 'App\\Models\\User')->count() }}"
                icon="lucide-bell" class="bg-info/10" />
            <x-stat title="Unread Notifications" value="{{ \Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_type', 'App\\Models\\User')->whereNull('read_at')->count() }}"
                icon="lucide-mail" class="bg-warning/10" />
            <x-stat title="Read Notifications" value="{{ \Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_type', 'App\\Models\\User')->whereNotNull('read_at')->count() }}"
                icon="lucide-check-circle" class="bg-success/10" />
        </div>

        {{-- Notifications Table --}}
        <x-table :headers="[
            ['key' => 'user_name', 'label' => 'Recipient'],
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'read_at', 'label' => 'Status'],
            ['key' => 'data', 'label' => 'Data'],
            ['key' => 'created_at', 'label' => 'Created'],
        ]" :rows="$notifications" :sort-by="$sortBy" with-pagination>
            @scope('cell_user_name', $notification)
                <div class="flex items-center gap-2">
                    <x-avatar placeholder="{{ strtoupper(substr($notification->user_name, 0, 1)) }}"
                        placeholder-text-class="text-sm font-bold"
                        placeholder-bg-class="bg-primary text-primary-content"
                        class="!w-8" />
                    <div>
                        <div class="font-medium">{{ $notification->user_name }}</div>
                        <div class="text-xs text-base-content/60">{{ $notification->user_email }}</div>
                    </div>
                </div>
            @endscope

            @scope('cell_type', $notification)
                <code class="text-xs bg-base-200 px-2 py-1 rounded">{{ $this->formatNotificationType($notification->type) }}</code>
            @endscope

            @scope('cell_read_at', $notification)
                @if ($notification->read_at)
                    <x-badge value="Read" class="badge-success" />
                @else
                    <x-badge value="Unread" class="badge-warning" />
                @endif
            @endscope

            @scope('cell_data', $notification)
                <div class="max-w-xs">
                    <pre class="text-xs bg-base-200 p-2 rounded overflow-hidden text-ellipsis whitespace-nowrap">{{ $this->getNotificationDataPreview($notification->data) }}</pre>
                </div>
            @endscope

            @scope('cell_created_at', $notification)
                <div class="flex flex-col">
                    <span>{{ \Carbon\Carbon::parse($notification->created_at)->format('M d, Y H:i') }}</span>
                    <span class="text-xs text-base-content/60">{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</span>
                </div>
            @endscope
        </x-table>
    </x-card>
</div>
