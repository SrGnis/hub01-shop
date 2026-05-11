@props([
    'items' => [],
    'current' => null,
])

@php
    $resolvedItems = count($items) > 0
        ? $items
        : [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-panel-left', 'route' => route('platform.dashboard')],
            ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell', 'route' => route('platform.notifications')],
            ['key' => 'collections', 'label' => 'Collections', 'icon' => 'lucide-folder-open', 'route' => route('platform.collections')],
            ['key' => 'projects', 'label' => 'Projects', 'icon' => 'lucide-package', 'route' => route('platform.projects')],
            ['key' => 'analytics', 'label' => 'Analytics', 'icon' => 'chart-bar', 'route' => route('platform.analytics')],
        ];
@endphp

<x-card class="!p-2" role="navigation" aria-label="Platform sections">
    <x-menu class="w-full">
        @foreach ($resolvedItems as $item)
            @php
                $isActive = $item['active'] ?? ($current && ($item['key'] ?? null) === $current);
            @endphp

            <x-menu-item
                :title="$item['label'] ?? ''"
                :icon="$item['icon'] ?? null"
                :link="$item['route'] ?? null"
                :class="$isActive ? 'bg-base-300 font-medium rounded-box' : ''"
                :aria-current="$isActive ? 'page' : null"
            />
        @endforeach
    </x-menu>
</x-card>
