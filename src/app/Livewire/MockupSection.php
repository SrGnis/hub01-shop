<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Platform Mockup')]
class MockupSection extends Component
{
    public string $section;

    public string $analyticsMode = 'daily';

    private array $analyticsDailyDatasets = [
        [
            'label' => 'core-mod',
            'data' => [12, 18, 14, 22, 19, 25, 21],
            'borderColor' => '#3b82f6',
            'backgroundColor' => 'rgba(59,130,246,0.15)',
            'tension' => 0.35,
        ],
        [
            'label' => 'qol-tools',
            'data' => [7, 9, 8, 13, 11, 15, 14],
            'borderColor' => '#10b981',
            'backgroundColor' => 'rgba(16,185,129,0.15)',
            'tension' => 0.35,
        ],
        [
            'label' => 'sound-remix',
            'data' => [4, 6, 5, 8, 9, 10, 11],
            'borderColor' => '#f59e0b',
            'backgroundColor' => 'rgba(245,158,11,0.15)',
            'tension' => 0.35,
        ],
    ];

    private array $analyticsCumulativeDatasets = [
        [
            'label' => 'core-mod',
            'data' => [12, 30, 44, 66, 85, 110, 131],
            'borderColor' => '#3b82f6',
            'backgroundColor' => 'rgba(59,130,246,0.15)',
            'tension' => 0.35,
        ],
        [
            'label' => 'qol-tools',
            'data' => [7, 16, 24, 37, 48, 63, 77],
            'borderColor' => '#10b981',
            'backgroundColor' => 'rgba(16,185,129,0.15)',
            'tension' => 0.35,
        ],
        [
            'label' => 'sound-remix',
            'data' => [4, 10, 15, 23, 32, 42, 53],
            'borderColor' => '#f59e0b',
            'backgroundColor' => 'rgba(245,158,11,0.15)',
            'tension' => 0.35,
        ],
    ];

    public array $analyticsChart = [
        'type' => 'line',
        'data' => [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'datasets' => [],
        ],
        'options' => [
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
                'axis' => 'x',
            ],
            'elements' => [
                'point' => [
                    'hitRadius' => 18,
                    'hoverRadius' => 7,
                    'radius' => 3,
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

    protected array $allowedSections = [
        'dashboard',
        'notifications',
        'collections',
        'projects',
        'analytics',
    ];

    public function mount(string $section): void
    {
        abort_unless(in_array($section, $this->allowedSections, true), 404);

        $this->section = $section;
        $this->analyticsChart['data']['datasets'] = $this->analyticsDailyDatasets;
    }

    public function toggleAnalyticsMode(): void
    {
        $this->analyticsMode = $this->analyticsMode === 'daily' ? 'cumulative' : 'daily';

        $this->analyticsChart['data']['datasets'] = $this->analyticsMode === 'daily'
            ? $this->analyticsDailyDatasets
            : $this->analyticsCumulativeDatasets;
    }

    public function updatedAnalyticsMode(string $value): void
    {
        $this->analyticsChart['data']['datasets'] = $value === 'daily'
            ? $this->analyticsDailyDatasets
            : $this->analyticsCumulativeDatasets;
    }

    public function render()
    {
        $sections = [
            'dashboard' => ['label' => 'Dashboard', 'href' => route('mockup.section', ['section' => 'dashboard']), 'icon' => 'layout-panel-left'],
            'notifications' => ['label' => 'Notifications', 'href' => route('mockup.section', ['section' => 'notifications']),'icon' => 'bell'],
            'collections' => ['label' => 'Collections', 'href' => route('mockup.section', ['section' => 'collections']), 'icon' => 'folder-open'],
            'projects' => ['label' => 'Projects', 'href' => route('mockup.section', ['section' => 'projects']), 'icon' => 'package'],
            'analytics' => ['label' => 'Analytics', 'href' => route('mockup.section', ['section' => 'analytics']), 'icon' => 'chart-no-axes-combined'],
        ];

        $view = 'livewire.mockups.'.$this->section;

        return view($view, [
            'section' => $this->section,
            'sections' => $sections,
            'currentUser' => Auth::user(),
        ]);
    }
}
