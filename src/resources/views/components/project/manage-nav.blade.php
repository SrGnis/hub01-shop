@props([
    'projectType',
    'project',
    'current' => 'general',
])

@php
    $items = [
        ['key' => 'general', 'label' => 'General', 'icon' => 'lucide-settings', 'route' => route('project.manage', ['projectType' => $projectType, 'project' => $project, 'section' => 'general'])],
        ['key' => 'description', 'label' => 'Description', 'icon' => 'lucide-file-text', 'route' => route('project.manage', ['projectType' => $projectType, 'project' => $project, 'section' => 'description'])],
        ['key' => 'tags', 'label' => 'Tags', 'icon' => 'lucide-tag', 'route' => route('project.manage', ['projectType' => $projectType, 'project' => $project, 'section' => 'tags'])],
        ['key' => 'links', 'label' => 'Links', 'icon' => 'lucide-link', 'route' => route('project.manage', ['projectType' => $projectType, 'project' => $project, 'section' => 'links'])],
        ['key' => 'members', 'label' => 'Members', 'icon' => 'lucide-users', 'route' => route('project.manage', ['projectType' => $projectType, 'project' => $project, 'section' => 'members'])],
        ['key' => 'danger', 'label' => 'Danger Zone', 'icon' => 'lucide-alert-triangle', 'route' => route('project.manage', ['projectType' => $projectType, 'project' => $project, 'section' => 'danger'])],
    ];
@endphp

<x-card class="!p-2" role="navigation" aria-label="Project management sections">
    <x-menu class="w-full">
        @foreach ($items as $item)
            @php
                $isActive = $item['key'] === $current;
            @endphp

            <x-menu-item
                :title="$item['label']"
                :icon="$item['icon']"
                :link="$item['route']"
                :class="$isActive ? 'bg-base-300 font-medium rounded-box' : ''"
                :aria-current="$isActive ? 'page' : null"
            />
        @endforeach
    </x-menu>
</x-card>
