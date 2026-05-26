<?php

namespace App\Services;

use App\Enums\CollectionSystemType;
use App\Models\CollectionEntry;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectVersionDailyDownload;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AnalyticsService
{
    private const TOP_PROJECTS_LIMIT = 4;

    private const CHART_SERIES_COLORS = [
        '#3B82F6',
        '#10B981',
        '#F59E0B',
        '#8B5CF6',
        '#EC4899',
        '#06B6D4',
        '#84CC16',
        '#F97316',
        '#EF4444',
        '#14B8A6',
        '#6B7280', // Other
    ];

    /**
     * @return array<int, array{key: string, label: string, value: int, icon: string, description: string}>
     */
    public function getSummaryMetricsForUser(User $user): array
    {
        $projectIds = $this->getActiveProjectIdsForUser($user);

        return $this->getSummaryMetricsForProjectIds($projectIds);
    }

    /**
     * @return array<int, array{key: string, label: string, value: int, icon: string, description: string}>
     */
    public function getSummaryMetricsForProject(Project $project): array
    {
        return $this->getSummaryMetricsForProjectIds(collect([$project->id]));
    }

    /**
     * @param  Collection<int, int>  $projectIds
     * @return array<int, array{key: string, label: string, value: int, icon: string, description: string}>
     */
    private function getSummaryMetricsForProjectIds(Collection $projectIds): array
    {

        if ($projectIds->isEmpty()) {
            return $this->buildSummaryMetrics(0, 0);
        }

        $downloadsTotal = (int) ProjectVersionDailyDownload::query()
            ->join('project_version', 'project_version.id', '=', 'project_version_daily_download.project_version_id')
            ->whereIn('project_version.project_id', $projectIds)
            ->sum('project_version_daily_download.downloads');

        $favoritesTotal = (int) CollectionEntry::query()
            ->join('collection', 'collection.uid', '=', 'collection_entry.collection_uid')
            ->join('project', 'project.id', '=', 'collection_entry.project_id')
            ->whereIn('project.id', $projectIds)
            ->where('collection.system_type', CollectionSystemType::FAVORITES->value)
            ->count();

        return $this->buildSummaryMetrics($downloadsTotal, $favoritesTotal);
    }

    /**
     * @return array<int, array{key: string, label: string, value: int, icon: string, description: string}>
     */
    private function buildSummaryMetrics(int $downloads, int $favorites): array
    {
        return [
            [
                'key' => 'downloads',
                'label' => 'Downloads',
                'value' => $downloads,
                'icon' => 'lucide-download',
                'description' => 'Total downloads across projects in your active workspace.',
            ],
            [
                'key' => 'favorites',
                'label' => 'Favorites',
                'value' => $favorites,
                'icon' => 'lucide-star',
                'description' => 'Times those projects were added to user favorites collections.',
            ]
        ];
    }

    /**
     * @return array{
     *     mode: 'daily'|'cumulative',
     *     labels: array<int, string>,
     *     datasets: array<int, array{key: string, label: string, color: string, data: array<int, int>}>
     * }
     */
    public function getChartForUser(User $user, string $mode = 'daily', int $days = 30): array
    {
        $resolvedMode = $mode === 'cumulative' ? 'cumulative' : 'daily';
        $projectIds = $this->getActiveProjectIdsForUser($user);

        return $this->getChartForProjectIds($projectIds, $resolvedMode, $days);
    }

    /**
     * @return array{
     *     mode: 'daily'|'cumulative',
     *     labels: array<int, string>,
     *     datasets: array<int, array{key: string, label: string, color: string, data: array<int, int>}>
     * }
     */
    public function getChartForProject(Project $project, string $mode = 'daily', int $days = 30): array
    {
        $resolvedMode = $mode === 'cumulative' ? 'cumulative' : 'daily';

        return $this->getChartForProjectIds(collect([$project->id]), $resolvedMode, $days);
    }

    /**
     * @param  Collection<int, int>  $projectIds
     * @return array{
     *     mode: 'daily'|'cumulative',
     *     labels: array<int, string>,
     *     datasets: array<int, array{key: string, label: string, color: string, data: array<int, int>}>
     * }
     */
    private function getChartForProjectIds(Collection $projectIds, string $mode, int $days): array
    {
        $resolvedDays = max(1, $days);

        if ($projectIds->isEmpty()) {
            return $this->emptyChart($mode, $resolvedDays);
        }

        return $this->buildChart($projectIds, $mode, $resolvedDays);
    }

    /**
     * @return Collection<int, int>
     */
    private function getActiveProjectIdsForUser(User $user): Collection
    {
        return Membership::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('project_id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * @param  Collection<int, int>  $projectIds
     * @return array{
     *     mode: 'daily'|'cumulative',
     *     labels: array<int, string>,
     *     datasets: array<int, array{key: string, label: string, color: string, data: array<int, int>}>
     * }
     */
    private function buildChart(Collection $projectIds, string $mode, int $days): array
    {
        $labels = $this->buildLabels($days);

        $startDate = CarbonImmutable::today()->subDays($days - 1)->toDateString();

        $topProjectRows = ProjectVersionDailyDownload::query()
            ->join('project_version', 'project_version.id', '=', 'project_version_daily_download.project_version_id')
            ->join('project', 'project.id', '=', 'project_version.project_id')
            ->whereIn('project_version.project_id', $projectIds)
            ->where('project_version_daily_download.date', '>=', $startDate)
            ->groupBy('project.id', 'project.slug', 'project.name')
            ->selectRaw('project.id as project_id, project.slug as project_slug, project.name as project_name, SUM(project_version_daily_download.downloads) as total')
            ->orderByDesc('total')
            ->limit(self::TOP_PROJECTS_LIMIT)
            ->get();

        /** @var Collection<int, int> $topProjectIds */
        $topProjectIds = $topProjectRows
            ->pluck('project_id')
            ->map(fn ($id) => (int) $id);

        $baselineByProject = collect();
        $baselineOther = 0;

        if ($mode === 'cumulative') {
            $baselineByProject = ProjectVersionDailyDownload::query()
                ->join('project_version', 'project_version.id', '=', 'project_version_daily_download.project_version_id')
                ->whereIn('project_version.project_id', $topProjectIds)
                ->where('project_version_daily_download.date', '<', $startDate)
                ->groupBy('project_version.project_id')
                ->selectRaw('project_version.project_id as project_id, SUM(project_version_daily_download.downloads) as total')
                ->get()
                ->mapWithKeys(fn ($row) => [(int) $row->project_id => (int) $row->total]);

            $baselineOther = (int) ProjectVersionDailyDownload::query()
                ->join('project_version', 'project_version.id', '=', 'project_version_daily_download.project_version_id')
                ->whereIn('project_version.project_id', $projectIds)
                ->whereNotIn('project_version.project_id', $topProjectIds)
                ->where('project_version_daily_download.date', '<', $startDate)
                ->sum('project_version_daily_download.downloads');
        }

        $downloadsByProjectDate = ProjectVersionDailyDownload::query()
            ->join('project_version', 'project_version.id', '=', 'project_version_daily_download.project_version_id')
            ->whereIn('project_version.project_id', $topProjectIds)
            ->where('project_version_daily_download.date', '>=', $startDate)
            ->groupBy('project_version.project_id', 'project_version_daily_download.date')
            ->orderBy('project_version_daily_download.date')
            ->selectRaw('project_version.project_id as project_id, project_version_daily_download.date as date, SUM(project_version_daily_download.downloads) as total')
            ->get();

        $downloadsByDateOther = ProjectVersionDailyDownload::query()
            ->join('project_version', 'project_version.id', '=', 'project_version_daily_download.project_version_id')
            ->whereIn('project_version.project_id', $projectIds)
            ->whereNotIn('project_version.project_id', $topProjectIds)
            ->where('project_version_daily_download.date', '>=', $startDate)
            ->groupBy('project_version_daily_download.date')
            ->orderBy('project_version_daily_download.date')
            ->selectRaw('project_version_daily_download.date as date, SUM(project_version_daily_download.downloads) as total')
            ->get()
            ->mapWithKeys(function ($row): array {
                $date = $row->date instanceof \DateTimeInterface
                    ? $row->date->format('Y-m-d')
                    : substr((string) $row->date, 0, 10);

                return [$date => (int) $row->total];
            });

        $datasets = [];

        foreach ($topProjectRows as $index => $projectRow) {
            $projectId = (int) $projectRow->project_id;

            $projectByDate = $downloadsByProjectDate
                ->where('project_id', $projectId)
                ->mapWithKeys(function ($row): array {
                    $date = $row->date instanceof \DateTimeInterface
                        ? $row->date->format('Y-m-d')
                        : substr((string) $row->date, 0, 10);

                    return [$date => (int) $row->total];
                });

            $datasets[] = [
                'key' => 'project_' . $projectId,
                'label' => (string) ($projectRow->project_name ?: $projectRow->project_slug ?: ('Project ' . $projectId)),
                'color' => self::CHART_SERIES_COLORS[$index] ?? '#3B82F6',
                'data' => $this->valuesByLabels(
                    labels: $labels,
                    byDate: $projectByDate,
                    cumulative: $mode === 'cumulative',
                    initial: (int) ($baselineByProject->get($projectId) ?? 0),
                ),
            ];
        }

        if ($baselineOther > 0 || $downloadsByDateOther->isNotEmpty()) {
            $datasets[] = [
                'key' => 'other',
                'label' => 'Other',
                'color' => self::CHART_SERIES_COLORS[count(self::CHART_SERIES_COLORS) - 1],
                'data' => $this->valuesByLabels(
                    labels: $labels,
                    byDate: $downloadsByDateOther,
                    cumulative: $mode === 'cumulative',
                    initial: $baselineOther,
                ),
            ];
        }

        return [
            'mode' => $mode,
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function buildLabels(int $days): array
    {
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = CarbonImmutable::today()->subDays($i)->toDateString();
        }

        return $labels;
    }

    /**
     * @param  array<int, string>  $labels
     * @param  Collection<string, int>  $byDate
     * @return array<int, int>
     */
    private function valuesByLabels(array $labels, Collection $byDate, bool $cumulative, int $initial = 0): array
    {
        $values = [];
        $running = max(0, $initial);

        foreach ($labels as $label) {
            $current = (int) ($byDate->get($label) ?? 0);
            $running += $current;
            $values[] = $cumulative ? $running : $current;
        }

        return $values;
    }

    /**
     * @return array{mode: 'daily'|'cumulative', labels: array<int, string>, datasets: array<int, array{key: string, label: string, color: string, data: array<int, int>}>}
     */
    private function emptyChart(string $mode, int $days): array
    {
        $labels = $this->buildLabels($days);

        return [
            'mode' => $mode,
            'labels' => $labels,
            'datasets' => [],
        ];
    }
}
