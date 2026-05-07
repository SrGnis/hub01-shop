@php
    $sections = [
        'dashboard' => ['label' => 'Dashboard', 'href' => route('mockup.section', ['section' => 'dashboard'])],
        'notifications' => ['label' => 'Notifications', 'href' => route('mockup.section', ['section' => 'notifications'])],
        'collections' => ['label' => 'Collections', 'href' => route('mockup.section', ['section' => 'collections'])],
        'projects' => ['label' => 'Projects', 'href' => route('mockup.section', ['section' => 'projects'])],
        'analytics' => ['label' => 'Analytics', 'href' => route('mockup.section', ['section' => 'analytics'])],
    ];

    $currentUser = auth()->user();

    $mockNotifications = [
        ['icon' => 'lucide-bell', 'title' => 'Your project has a new comment', 'date' => '2026-05-06', 'read' => false],
        ['icon' => 'lucide-check-circle-2', 'title' => 'Project version approved', 'date' => '2026-05-05', 'read' => true],
        ['icon' => 'lucide-upload', 'title' => 'New download milestone reached', 'date' => '2026-05-04', 'read' => true],
    ];

    $mockAnalytics = [
        ['label' => 'Downloads', 'value' => '12,430', 'icon' => 'lucide-download'],
        ['label' => 'Favorites', 'value' => '842', 'icon' => 'lucide-heart'],
    ];
@endphp

<x-layouts.two-column-base>
    @if ($section !== 'dashboard')
        <x-slot:header>
            <x-card>
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl md:text-3xl font-bold">Platform Settings Mockup</h1>
                    <p class="text-sm text-base-content/70">
                        UI mockup route for section:
                        <span class="font-semibold">{{ $section }}</span>
                    </p>
                </div>
            </x-card>
        </x-slot:header>
    @endif

    <x-slot:left>
        <x-column-navigation
            title="Sections"
            :items="$sections"
            :active="$section"
        />
    </x-slot:left>

    <x-slot:right>
        @if ($section === 'dashboard')
            <div class="space-y-4">
                <x-card>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <x-avatar
                            placeholder="{{ strtoupper(substr($currentUser?->name ?? 'Guest', 0, 1)) }}"
                            placeholder-text-class="text-2xl font-bold"
                            placeholder-bg-class="bg-primary text-primary-content"
                            class="!w-20"
                            image="{{ $currentUser?->getAvatarUrl() }}"
                        >
                            <x-slot:title class="text-xl !font-bold pl-2">
                                {{ $currentUser?->name ?? 'Guest User' }}
                            </x-slot:title>
                            <x-slot:subtitle class="pl-2 mt-1">
                                @if ($currentUser)
                                    <a href="{{ route('user.profile', $currentUser) }}" class="link link-primary text-sm">View profile</a>
                                @else
                                    <span class="text-sm text-base-content/60">View profile</span>
                                @endif
                            </x-slot:subtitle>
                        </x-avatar>

                        <x-button
                            link="{{ $currentUser ? route('user.profile.edit') : '#' }}"
                            icon="lucide-cog"
                            label="Profile Settings"
                            class="btn-ghost"
                        />
                    </div>
                </x-card>

                <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                    <div class="xl:col-span-8">
                        <x-card class="h-full">
                            <div class="space-y-3">
                                <h2 class="text-lg font-semibold">Notifications</h2>

                                @foreach ($mockNotifications as $notification)
                                    <x-card class="!p-4 border border-base-300">
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <x-icon name="{{ $notification['icon'] }}" class="w-4 h-4 text-primary" />
                                                <p class="font-medium">{{ $notification['title'] }}</p>
                                            </div>

                                            <div class="flex items-center justify-between text-sm text-base-content/70">
                                                <span>{{ $notification['date'] }}</span>
                                                <span class="badge {{ $notification['read'] ? 'badge-ghost' : 'badge-primary' }}">
                                                    {{ $notification['read'] ? 'Read' : 'Unread' }}
                                                </span>
                                            </div>
                                        </div>
                                    </x-card>
                                @endforeach

                                <a href="{{ route('mockup.section', ['section' => 'notifications']) }}" class="link link-primary text-sm">
                                    View all
                                </a>
                            </div>
                        </x-card>
                    </div>

                    <div class="xl:col-span-4">
                        <x-card class="h-full">
                            <div class="space-y-3">
                                <h2 class="text-lg font-semibold">Analytics</h2>

                                <div class="space-y-2">
                                    @foreach ($mockAnalytics as $stat)
                                        <div class="grid grid-cols-3 items-center border border-base-300 rounded-lg p-3">
                                            <div class="col-span-2">
                                                <p class="text-sm text-base-content/70">{{ $stat['label'] }}</p>
                                                <p class="text-xl font-bold">{{ $stat['value'] }}</p>
                                            </div>
                                            <div class="flex justify-end">
                                                <x-icon name="{{ $stat['icon'] }}" class="w-5 h-5 text-primary" />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <a href="{{ route('mockup.section', ['section' => 'analytics']) }}" class="link link-primary text-sm">
                                    View more
                                </a>
                            </div>
                        </x-card>
                    </div>
                </div>
            </div>
        @else
            <x-card class="min-h-[28rem]">
                <div class="space-y-3">
                    <h2 class="text-xl font-semibold">{{ ucfirst($section) }} section</h2>
                    <p class="text-base-content/70">
                        This is the main content area for the selected section. We can now iterate on each
                        section design independently while keeping a shared layout shell.
                    </p>
                </div>
            </x-card>
        @endif
    </x-slot:right>

    @if ($section !== 'dashboard')
        <x-slot:footer>
            <x-card>
                <p class="text-sm text-base-content/70">
                    Mockup footer area for shared actions, help links, or section-specific summaries.
                </p>
            </x-card>
        </x-slot:footer>
    @endif
</x-layouts.two-column-base>
