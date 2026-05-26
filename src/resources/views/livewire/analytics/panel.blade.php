<div class="space-y-6">
    <x-card>
        <div class="grid grid-cols-2 gap-4" aria-live="polite">
            @foreach ($this->summaryMetrics as $metric)
                <x-platform.dashboard-stat
                    :title="$metric['label']"
                    :value="number_format((int) $metric['value'])"
                    :icon="$metric['icon']"
                    accent-class="text-primary"
                />
            @endforeach
        </div>
    </x-card>

    <x-card aria-live="polite">
        <x-header
            title="Trends"
            :subtitle="$mode === 'daily' ? 'Per-day values for downloads and favorites.' : 'Running totals across the same period.'"
        >
            <x-slot:actions>
                <div class="flex items-center gap-2">
                    <div class="join">
                        <x-button
                            label="Daily"
                            wire:click="setMode('daily')"
                            class="join-item btn-sm {{ $mode === 'daily' ? 'btn-primary' : 'btn-ghost' }}"
                            aria-label="Switch analytics to daily mode"
                        />
                        <x-button
                            label="Cumulative"
                            wire:click="setMode('cumulative')"
                            class="join-item btn-sm {{ $mode === 'cumulative' ? 'btn-primary' : 'btn-ghost' }}"
                            aria-label="Switch analytics to cumulative mode"
                        />
                    </div>

                    <x-button
                        label="Export CSV"
                        icon="lucide-download"
                        wire:click="exportCsv"
                        class="btn-sm btn-outline"
                        aria-label="Export analytics table as CSV"
                    />
                </div>
            </x-slot:actions>
        </x-header>

        @if (empty($this->chart['labels']) || empty($this->chart['datasets']))
            <div class="text-center py-12">
                <x-icon name="lucide-chart-no-axes-column" class="w-14 h-14 mx-auto mb-4 text-base-content/40" />
                <h3 class="text-lg font-medium mb-2">No analytics data yet</h3>
                <p class="text-sm text-base-content/60">Data will appear here once your projects receive activity.</p>
            </div>
        @else
            <div class="h-96">
                <x-chart wire:model="analyticsChart" class="h-full" />
            </div>

            <div class="overflow-x-auto mt-6">
                <table class="table table-zebra table-pin-rows" aria-label="Analytics trend table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            @foreach ($this->chart['labels'] ?? [] as $label)
                                <th class="whitespace-nowrap">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->chart['datasets'] ?? [] as $dataset)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2 font-medium">
                                        <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: {{ $dataset['color'] }}"></span>
                                        {{ $dataset['label'] }}
                                    </div>
                                </td>
                                @foreach ($dataset['data'] as $point)
                                    <td class="text-sm">{{ number_format((int) $point) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</div>
