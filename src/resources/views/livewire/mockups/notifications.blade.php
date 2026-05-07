@php
    $mockNotifications = [
        ['icon' => 'lucide-bell', 'title' => 'Your project has a new comment', 'date' => '2026-05-06', 'read' => false],
        ['icon' => 'lucide-check-circle-2', 'title' => 'Project version approved', 'date' => '2026-05-05', 'read' => true],
        ['icon' => 'lucide-upload', 'title' => 'New download milestone reached', 'date' => '2026-05-04', 'read' => true],
        ['icon' => 'lucide-message-square', 'title' => 'New maintainer invitation', 'date' => '2026-05-03', 'read' => false],
    ];
@endphp

<x-layouts.two-column-base>
    <x-slot:header>
        <x-header title="Notifications" icon="bell" icon-classes="w-6 h-6" subtitle="Review recent platform activity, manage read state, and clean up old notifications.">
            <x-slot:actions>
                <x-input placeholder="Search notifications" icon="lucide-search" class="w-full sm:w-80" />

                <x-dropdown>
                    <x-slot:trigger>
                        <x-button label="Filter order" icon="lucide-arrow-up-down" class="btn-ghost" />
                    </x-slot:trigger>
                    <x-menu>
                        <x-menu-item title="Newest first" />
                        <x-menu-item title="Oldest first" />
                        <x-menu-item title="Unread first" />
                        <x-menu-item title="Read first" />
                    </x-menu>
                </x-dropdown>

                <x-button label="Mark all as read" icon="lucide-eye" class="btn-primary" />
            </x-slot:actions>
        </x-header>
    </x-slot:header>

    <x-slot:left>
        <x-column-navigation :items="$sections" :active="$section" />
    </x-slot:left>

    <x-slot:right>
        <div class="space-y-4 min-h-[28rem]">
            <x-card>
                <div class="space-y-3">
                    <h2 class="text-lg font-semibold">All notifications</h2>

                    @foreach ($mockNotifications as $notification)
                        <x-card class="!p-4 bg-base-200">
                            <div class="flex justify-between space-y-2">
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <x-icon name="{{ $notification['icon'] }}" class="w-4 h-4 text-primary" />
                                        <p class="font-medium truncate">{{ $notification['title'] }}</p>
                                    </div>

                                    <div class="flex items-center gap-2 text-sm text-base-content/70">
                                        <span>{{ $notification['date'] }}</span>
                                        <span class="badge {{ $notification['read'] ? 'badge-accent' : 'badge-primary' }}">
                                            {{ $notification['read'] ? 'Read' : 'Unread' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0 self-center">
                                    <x-button label="Expand" icon-right="lucide-chevron-down" class="btn-ghost btn-md " />
                                    <x-button icon="lucide-eye" tooltip="Mark as read" class="btn-ghost btn-md " />
                                    <x-button icon="lucide-trash-2" tooltip="Delete" class="btn-ghost btn-md text-error" />
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            </x-card>
        </div>
    </x-slot:right>
</x-layouts.two-column-base>
