<?php

namespace App\Livewire\Platform;

use App\Services\AnalyticsService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;

class Analytics extends Component
{
    #[Session(key: 'platform-analytics-mode')]
    public string $mode = 'daily';

    public array $analyticsChart = [];

    private AnalyticsService $analyticsService;

    public function boot(AnalyticsService $analyticsService): void
    {
        $this->analyticsService = $analyticsService;
    }

    public function mount(): void
    {
        if (! in_array($this->mode, ['daily', 'cumulative'], true)) {
            $this->mode = 'daily';
        }

        $this->refreshAnalyticsChart();
    }

    public function setMode(string $mode): void
    {
        if (! in_array($mode, ['daily', 'cumulative'], true)) {
            return;
        }

        $this->mode = $mode;
        unset($this->chart);
        $this->refreshAnalyticsChart();
    }

    public function updatedMode(): void
    {
        if (! in_array($this->mode, ['daily', 'cumulative'], true)) {
            $this->mode = 'daily';
        }

        unset($this->chart);
        $this->refreshAnalyticsChart();
    }

    #[Computed]
    public function summaryMetrics(): array
    {
        return $this->analyticsService->getSummaryMetricsForUser(Auth::user());
    }

    #[Computed]
    public function chart(): array
    {
        return $this->analyticsService->getChartForUser(Auth::user(), $this->mode);
    }

    private function refreshAnalyticsChart(): void
    {
        $datasets = array_map(function (array $dataset): array {
            $color = (string) ($dataset['color'] ?? '#3B82F6');

            return [
                'label' => (string) ($dataset['label'] ?? 'Series'),
                'data' => array_map(static fn ($point): int => (int) $point, $dataset['data'] ?? []),
                'borderColor' => $color,
                'backgroundColor' => $this->transparentizeColor($color, 0.18),
                'tension' => 0.35,
                'pointRadius' => 2,
                'pointHoverRadius' => 6,
                'pointHitRadius' => 16,
                'fill' => false,
            ];
        }, $this->chart['datasets'] ?? []);

        $this->analyticsChart = [
            'type' => 'line',
            'data' => [
                'labels' => $this->chart['labels'] ?? [],
                'datasets' => $datasets,
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'interaction' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'axis' => 'x',
                ],
                'elements' => [
                    'point' => [
                        'hitRadius' => 16,
                        'hoverRadius' => 6,
                        'radius' => 2,
                    ],
                ],
                'plugins' => [
                    'legend' => [
                        'position' => 'right',
                        'align' => 'start',
                    ],
                    'tooltip' => [
                        'mode' => 'index',
                        'intersect' => false,
                    ],
                ],
            ],
        ];
    }

    private function transparentizeColor(string $hexColor, float $alpha = 0.15): string
    {
        $hex = ltrim($hexColor, '#');

        if (strlen($hex) !== 6) {
            return 'rgba(59,130,246,' . $alpha . ')';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return sprintf('rgba(%d,%d,%d,%.2F)', $r, $g, $b, max(0, min(1, $alpha)));
    }

    public function render()
    {
        return view('livewire.platform.analytics');
    }

    public function exportCsv()
    {
        $labels = $this->chart['labels'] ?? [];
        $datasets = $this->chart['datasets'] ?? [];

        $filename = sprintf(
            'analytics-%s-%s.csv',
            Str::slug($this->mode),
            now()->format('Ymd-His')
        );

        return response()->streamDownload(function () use ($labels, $datasets): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, array_merge(['Metric'], $labels));

            foreach ($datasets as $dataset) {
                $row = array_merge(
                    [(string) ($dataset['label'] ?? 'Series')],
                    array_map(static fn ($point): int => (int) $point, $dataset['data'] ?? [])
                );

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
