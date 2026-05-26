<?php

namespace Tests\Feature\Platform\Livewire;

use App\Livewire\Analytics\Panel;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDailyDownload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function mount_coerces_invalid_mode_and_set_mode_ignores_invalid_values(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Panel::class, ['scope' => 'workspace'])
            ->set('mode', 'invalid')
            ->assertSet('mode', 'daily')
            ->call('setMode', 'wrong')
            ->assertSet('mode', 'daily');
    }

    #[Test]
    public function mode_switch_refreshes_chart_payload_and_shape(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10'));

        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Chart Project']);
        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'status' => 'active']);
        $version = ProjectVersion::factory()->withoutDailyDownloads()->create(['project_id' => $project->id]);
        ProjectVersionDailyDownload::factory()->forVersion($version)->forDate('2026-05-09')->withDownloads(12)->create();
        ProjectVersionDailyDownload::factory()->forVersion($version)->forDate('2026-05-10')->withDownloads(8)->create();

        $this->actingAs($user);

        $component = Livewire::test(Panel::class, ['scope' => 'workspace'])
            ->assertSet('mode', 'daily');

        $daily = $component->get('chart');
        $this->assertArrayHasKey('labels', $daily);
        $this->assertArrayHasKey('datasets', $daily);

        $component->call('setMode', 'cumulative')->assertSet('mode', 'cumulative');
        $cumulative = $component->get('chart');
        $this->assertSame('cumulative', $cumulative['mode']);

        $analyticsChart = $component->get('analyticsChart');
        $dataset = $analyticsChart['data']['datasets'][0] ?? [];
        $this->assertArrayHasKey('borderColor', $dataset);
        $this->assertArrayHasKey('backgroundColor', $dataset);
        $this->assertArrayHasKey('pointRadius', $dataset);
    }

    #[Test]
    public function summary_metrics_shape_and_csv_export_work(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 14:15:16'));

        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Csv Project']);
        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'status' => 'active']);
        $version = ProjectVersion::factory()->withoutDailyDownloads()->create(['project_id' => $project->id]);
        ProjectVersionDailyDownload::factory()->forVersion($version)->forDate('2026-05-10')->withDownloads(9)->create();

        $this->actingAs($user);

        $component = Livewire::test(Panel::class, ['scope' => 'workspace']);
        $metrics = $component->get('summaryMetrics');
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('key', $metrics[0]);
        $this->assertArrayHasKey('value', $metrics[0]);

        $response = $component->instance()->exportCsv();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/csv; charset=UTF-8', (string) $response->headers->get('content-type'));
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('analytics-daily-20260510-141516.csv', $disposition);

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        $this->assertStringContainsString('Metric,', $csv);
        $this->assertStringContainsString('Csv Project', $csv);
    }
}
