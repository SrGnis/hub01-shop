<?php

namespace App\Livewire\Analytics;

use App\Models\Project;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Panel extends Component
{
    #[Locked]
    public string $scope = 'workspace';

    #[Locked]
    public ?Project $project = null;

    public string $mode = 'daily';

    public array $analyticsChart = [];

    private AnalyticsService $analyticsService;

    public function boot(AnalyticsService $analyticsService): void
    {
        $this->analyticsService = $analyticsService;
    }

    public function mount(string $scope = 'workspace', ?Project $project = null): void
    {
        $this->scope = in_array($scope, ['workspace', 'project'], true) ? $scope : 'workspace';
        $this->project = $project;

        if ($this->scope === 'project' && $this->project === null) {
            abort(404);
        }

        if ($this->scope === 'project') {
            Gate::authorize('update', $this->project);
        }

        $this->mode = session()->get($this->modeSessionKey(), 'daily');

        if (! in_array($this->mode, ['daily', 'cumulative'], true)) {
            $this->mode = 'daily';
        }

        session()->put($this->modeSessionKey(), $this->mode);

        $this->refreshAnalyticsChart();
    }

    public function setMode(string $mode): void
    {
        if (! in_array($mode, ['daily', 'cumulative'], true)) {
            return;
        }

        $this->mode = $mode;
        session()->put($this->modeSessionKey(), $this->mode);
        unset($this->chart);
        $this->refreshAnalyticsChart();
    }

    public function updatedMode(): void
    {
        if (! in_array($this->mode, ['daily', 'cumulative'], true)) {
            $this->mode = 'daily';
        }

        session()->put($this->modeSessionKey(), $this->mode);
        unset($this->chart);
        $this->refreshAnalyticsChart();
    }

    #[Computed]
    public function summaryMetrics(): array
    {
        if ($this->scope === 'project' && $this->project !== null) {
            return $this->analyticsService->getSummaryMetricsForProject($this->project);
        }

        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        return $this->analyticsService->getSummaryMetricsForUser($user);
    }

    #[Computed]
    public function chart(): array
    {
        if ($this->scope === 'project' && $this->project !== null) {
            return $this->analyticsService->getChartForProject($this->project, $this->mode);
        }

        $user = Auth::user();

        if ($user === null) {
            return [
                'mode' => $this->mode,
                'labels' => [],
                'datasets' => [],
            ];
        }

        return $this->analyticsService->getChartForUser($user, $this->mode);
    }

    public function exportCsv()
    {
        if ($this->scope === 'project') {
            Gate::authorize('update', $this->project);
        }

        $labels = $this->chart['labels'] ?? [];
        $datasets = $this->chart['datasets'] ?? [];

        $filename = sprintf(
            'analytics-%s-%s.csv',
            $this->filenameScopePrefix(),
            now()->format('Ymd-His')
        );

        return response()->streamDownload(function () use ($labels, $datasets): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, array_merge(
                [$this->escapeCsvCell('Metric')],
                array_map(fn ($label): string => $this->escapeCsvCell((string) $label), $labels)
            ));

            foreach ($datasets as $dataset) {
                $row = array_merge(
                    [$this->escapeCsvCell((string) ($dataset['label'] ?? 'Series'))],
                    array_map(static fn ($point): int => (int) $point, $dataset['data'] ?? [])
                );

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        return view('livewire.analytics.panel');
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

    private function escapeCsvCell(string $value): string
    {
        if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }

    private function modeSessionKey(): string
    {
        if ($this->scope === 'project') {
            $projectId = (int) ($this->project?->id ?? 0);

            return 'project-analytics-mode-' . $projectId;
        }

        return 'workspace-analytics-mode';
    }

    private function filenameScopePrefix(): string
    {
        if ($this->scope === 'project' && $this->project !== null) {
            return sprintf('project-%s-%s', Str::slug((string) $this->project->slug), Str::slug($this->mode));
        }

        return Str::slug($this->mode);
    }
}
