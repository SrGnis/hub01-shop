@php
    $mockAnalytics = [
        ['label' => 'Downloads', 'value' => '12,430', 'icon' => 'lucide-download'],
        ['label' => 'Favorites', 'value' => '842', 'icon' => 'lucide-heart'],
    ];
@endphp

<x-layouts.two-column-base>
    <x-slot:header>
        <x-header
            title="Statistics"
            subtitle="Track your global stats and daily download trends per project."
        />
    </x-slot:header>

    <x-slot:left>
        <x-column-navigation :items="$sections" :active="$section" />
    </x-slot:left>

    <x-slot:right>
        <div class="space-y-4 min-h-[28rem]">
            <x-card>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($mockAnalytics as $stat)
                        <x-mockup-stat
                            :title="$stat['label']"
                            :value="$stat['value']"
                            :icon="$stat['icon']"
                            accent-class="text-primary"
                        />
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h2 class="text-lg font-semibold">Daily downloads by project</h2>
                    <div class="join">
                        <input
                            class="join-item btn btn-sm"
                            type="radio"
                            name="analytics_mode"
                            aria-label="Daily"
                            wire:click="$set('analyticsMode', 'daily')"
                            @checked($analyticsMode === 'daily')
                        />
                        <input
                            class="join-item btn btn-sm"
                            type="radio"
                            name="analytics_mode"
                            aria-label="Cumulative"
                            wire:click="$set('analyticsMode', 'cumulative')"
                            @checked($analyticsMode === 'cumulative')
                        />
                    </div>
                </div>
                <x-chart wire:model="analyticsChart" class="w-full min-h-80" />
            </x-card>
        </div>
    </x-slot:right>
</x-layouts.two-column-base>
