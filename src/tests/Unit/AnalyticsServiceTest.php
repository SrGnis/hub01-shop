<?php

namespace Tests\Unit;

use App\Services\AnalyticsService;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDailyDownload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function no_active_memberships_returns_zeroed_summary(): void
    {
        $service = new AnalyticsService();
        $user = User::factory()->create();

        $metrics = $service->getSummaryMetricsForUser($user);
        $byKey = collect($metrics)->keyBy('key');

        $this->assertSame(0, $byKey['downloads']['value']);
        $this->assertSame(0, $byKey['favorites']['value']);
    }

    #[Test]
    public function summary_and_chart_scope_to_active_memberships_only(): void
    {
        Carbon::setTestNow('2026-05-10');
        $service = new AnalyticsService();
        $user = User::factory()->create();

        $active = Project::factory()->create(['name' => 'Active Project']);
        $inactive = Project::factory()->create(['name' => 'Inactive Project']);

        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $active->id, 'status' => 'active']);
        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $inactive->id, 'status' => 'pending']);

        $v1 = ProjectVersion::factory()->withoutDailyDownloads()->create(['project_id' => $active->id]);
        $v2 = ProjectVersion::factory()->withoutDailyDownloads()->create(['project_id' => $inactive->id]);

        ProjectVersionDailyDownload::factory()->forVersion($v1)->forDate('2026-05-10')->withDownloads(30)->create();
        ProjectVersionDailyDownload::factory()->forVersion($v2)->forDate('2026-05-10')->withDownloads(999)->create();

        $metrics = $service->getSummaryMetricsForUser($user);
        $this->assertSame(30, collect($metrics)->firstWhere('key', 'downloads')['value']);

        $chart = $service->getChartForUser($user, 'daily', 2);
        $labels = $chart['labels'];
        $dataset = collect($chart['datasets'])->firstWhere('label', 'Active Project');
        $this->assertSame(['2026-05-09', '2026-05-10'], $labels);
        $this->assertSame([0, 30], $dataset['data']);
    }

    #[Test]
    public function daily_missing_dates_zero_fill_and_invalid_mode_defaults_to_daily(): void
    {
        Carbon::setTestNow('2026-05-10');
        $service = new AnalyticsService();
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Gap Project']);
        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'status' => 'active']);
        $version = ProjectVersion::factory()->withoutDailyDownloads()->create(['project_id' => $project->id]);

        ProjectVersionDailyDownload::factory()->forVersion($version)->forDate('2026-05-08')->withDownloads(5)->create();
        ProjectVersionDailyDownload::factory()->forVersion($version)->forDate('2026-05-10')->withDownloads(7)->create();

        $chart = $service->getChartForUser($user, 'bad-mode', 3);
        $this->assertSame('daily', $chart['mode']);
        $dataset = collect($chart['datasets'])->firstWhere('label', 'Gap Project');
        $this->assertSame([5, 0, 7], $dataset['data']);
    }

    #[Test]
    public function cumulative_includes_baseline_and_other_bucket_with_stable_colors(): void
    {
        Carbon::setTestNow('2026-05-10');
        $service = new AnalyticsService();
        $user = User::factory()->create();

        $projects = collect(range(1, 5))->map(fn (int $i) => Project::factory()->create(['name' => 'P' . $i]));
        foreach ($projects as $project) {
            Membership::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'status' => 'active']);
            $version = ProjectVersion::factory()->withoutDailyDownloads()->create(['project_id' => $project->id]);
            ProjectVersionDailyDownload::factory()->forVersion($version)->forDate('2026-05-07')->withDownloads(10)->create(); // baseline
            ProjectVersionDailyDownload::factory()->forVersion($version)->forDate('2026-05-10')->withDownloads($project->name === 'P5' ? 1 : 20)->create();
        }

        $chart = $service->getChartForUser($user, 'cumulative', 3);

        $this->assertSame('cumulative', $chart['mode']);
        $this->assertCount(5, $chart['datasets']); // top 4 + Other
        $this->assertSame('Other', $chart['datasets'][4]['label']);
        $this->assertSame('#6B7280', $chart['datasets'][4]['color']);
        $this->assertGreaterThanOrEqual(10, $chart['datasets'][4]['data'][0]);
    }
}
