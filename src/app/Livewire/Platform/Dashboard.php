<?php

namespace App\Livewire\Platform;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    private DashboardService $dashboardService;

    public function boot(dashboardService $dashboardService): void
    {
        $this->dashboardService = $dashboardService;
    }

    #[Computed]
    public function profile(): array
    {
        return $this->dashboardService->getProfileForUser(Auth::user());
    }

    #[Computed]
    public function aggregateDownloads(): int
    {
        return $this->dashboardService->getAggregateDownloadsForUser(Auth::user());
    }

    #[Computed]
    public function aggregateFavorites(): int
    {
        return $this->dashboardService->getAggregateFavoritesForUser(Auth::user());
    }

    #[Computed]
    public function summaryMetrics(): array
    {
        return [
            [
                'key' => 'downloads',
                'label' => 'Downloads',
                'value' => $this->aggregateDownloads,
                'icon' => 'download',
            ],
            [
                'key' => 'favorites',
                'label' => 'Favorites',
                'value' => $this->aggregateFavorites,
                'icon' => 'star',
            ],
        ];
    }

    #[Computed]
    public function recentNotifications(): array
    {
        return $this->dashboardService->getRecentNotificationsForUser(Auth::user());
    }

    public function render()
    {
        return view('livewire.platform.dashboard');
    }
}
