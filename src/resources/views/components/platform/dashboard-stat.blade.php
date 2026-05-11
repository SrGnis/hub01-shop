@props([
    'title',
    'value',
    'icon' => 'lucide-bar-chart-3',
    'accentClass' => 'text-primary',
])

<x-card class="bg-base-200">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-lg text-base-content/70">{{ $title }}</p>
            <p class="text-2xl font-bold">{{ $value }}</p>
        </div>
        <x-icon :name="$icon" class="w-6 h-6 {{ $accentClass }}" />
    </div>
</x-card>
