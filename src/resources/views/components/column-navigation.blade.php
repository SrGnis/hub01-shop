@props([
    'items' => [],
    'active' => null,
])

<x-card>
    <nav class="flex flex-col gap-2">
        @foreach ($items as $key => $item)
            @php
                $label = is_array($item) ? ($item['label'] ?? ucfirst((string) $key)) : $item;
                $href = is_array($item) ? ($item['href'] ?? '#') : '#';
                $icon = is_array($item) ? ($item['icon'] ?? null) : null;
                $isActive = (string) $active === (string) $key;
            @endphp

            <x-button
                :link="$href"
                :label="$label"
                :icon="$icon"
                no-wire-navigate
                class="justify-start w-full {{ $isActive ? 'btn-primary font-semibold' : 'btn-ghost' }}"
            />
        @endforeach
    </nav>
</x-card>
