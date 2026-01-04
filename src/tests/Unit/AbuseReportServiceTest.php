<?php

namespace Tests\Unit;

use App\Models\AbuseReport;
use App\Models\Project;
use App\Models\ProjectVersion;
use App\Models\User;
use App\Services\AbuseReportService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbuseReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private AbuseReportService $abuseReportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->abuseReportService = new AbuseReportService();
    }

    public function test_create_report(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->create();

        $data = [
            'reason' => 'This project contains inappropriate material.',
            'reportable_id' => $project->id,
            'reportable_type' => Project::class, // needs to be fully qualified class name
            'reporter_id' => $user->id,
        ];

        $report = $this->abuseReportService->createReport($data);

        $this->assertInstanceOf(AbuseReport::class, $report);
        $this->assertEquals('pending', $report->status);
        $this->assertEquals($user->id, $report->reporter_id);
        $this->assertEquals($project->id, $report->reportable_id);
        $this->assertEquals(Project::class, $report->reportable_type);
        $this->assertDatabaseHas('abuse_reports', ['id' => $report->id]);
    }

    public function test_create_report_fails_without_verification(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Reporter must be a verified user.');

        $user = User::factory()->create(['email_verified_at' => null]); // unverified
        $project = Project::factory()->create();

        $data = [
            'reason' => 'Some reason',
            'reportable_id' => $project->id,
            'reportable_type' => Project::class,
            'reporter_id' => $user->id,
        ];

        $this->abuseReportService->createReport($data);
    }

    public function test_create_report_fails_with_non_existent_item(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Reported item does not exist.');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $data = [
            'reason' => 'Item does not exist',
            'reportable_id' => 99999,
            'reportable_type' => Project::class, // fully qualified class name
            'reporter_id' => $user->id,
        ];

        $this->abuseReportService->createReport($data);
    }

    public function test_validate_reporter_authenticated_and_verified(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // No exception should be thrown
        $this->expectNotToPerformAssertions();
        $this->abuseReportService->validateReporter($user);
    }
    public function test_validate_reporter_deactivated(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Reporter is deactivated.');

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'deactivated_at' => now(),
        ]);

        $this->abuseReportService->validateReporter($user);
    }

    public function test_validate_report_item_exists(): void
    {
        $project = Project::factory()->create();

        $this->expectNotToPerformAssertions();
        $this->abuseReportService->validateReportedItem(Project::class, $project->id);
    }

    public function test_validate_report_item_does_not_exist(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Reported item does not exist.');

        $this->abuseReportService->validateReportedItem(Project::class, 99999);
    }

    public function test_validate_report_item_invalid_type(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid reported item type.');

        $this->abuseReportService->validateReportedItem('NonExistentClass', 1);
    }

    public function test_get_reports_for_admin(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $project = Project::factory()->create();
        $version = ProjectVersion::factory()->create(['project_id' => $project->id]);

        // Create a pending report
        $report1 = AbuseReport::create([
            'reason' => 'Spam',
            'reportable_id' => $project->id,
            'reportable_type' => Project::class,
            'reporter_id' => $user->id,
            'status' => 'pending',
        ]);
        // Create a resolved report
        $report2 = AbuseReport::create([
            'reason' => 'Harassment',
            'reportable_id' => $version->id,
            'reportable_type' => ProjectVersion::class,
            'reporter_id' => $user->id,
            'status' => 'resolved',
        ]);

        // Get all reports (no pendingOnly filter)
        $reports = $this->abuseReportService->getReportsForAdmin(false);
        $this->assertCount(2, $reports);

        // Get only pending reports (default)
        $pendingReports = $this->abuseReportService->getReportsForAdmin(true);
        $this->assertCount(1, $pendingReports);
        $this->assertEquals('pending', $pendingReports->first()->status);
    }

    public function test_resolve_report(): void
    {
        $report = AbuseReport::factory()->create(['status' => 'pending']);
        $this->abuseReportService->resolveReport($report);
        $this->assertEquals('resolved', $report->fresh()->status);
    }

    public function test_reopen_report(): void
    {
        $report = AbuseReport::factory()->create(['status' => 'resolved']);
        $this->abuseReportService->reopenReport($report);
        $this->assertEquals('pending', $report->fresh()->status);
    }

    public function test_delete_report(): void
    {
        $report = AbuseReport::factory()->create();
        $this->abuseReportService->deleteReport($report);
        $this->assertSoftDeleted($report);
    }

    public function test_restore_report(): void
    {
        $report = AbuseReport::factory()->create();
        $report->delete(); // soft delete
        $restored = $this->abuseReportService->restoreReport($report->id);
        $this->assertFalse($restored->trashed());
    }

    public function test_count_pending_reports(): void
    {
        AbuseReport::factory()->pending()->count(3)->create();
        AbuseReport::factory()->resolved()->count(2)->create();
        $count = $this->abuseReportService->countPendingReports();
        $this->assertEquals(3, $count);
    }
}
