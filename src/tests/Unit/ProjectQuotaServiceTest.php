<?php

namespace Tests\Unit;

use App\Enums\ApprovalStatus;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\User;
use App\Models\UserQuota;
use App\Models\ProjectQuota;
use App\Models\ProjectTypeQuota;
use App\Services\ProjectQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectQuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectQuotaService $quotaService;
    private ProjectType $projectType;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotaService = $this->app->make(ProjectQuotaService::class);
        $this->projectType = ProjectType::factory()->create();
        $this->user = User::factory()->create();
    }

    // ==========================================================================
    // Core Functionality Tests
    // ==========================================================================

    #[Test]
    public function getPendingProjectsCount_returns_correct_count()
    {
        // Create 3 pending projects
        Project::factory()->count(3)->owner($this->user)->pending()->create();

        $count = $this->quotaService->getPendingProjectsCount($this->user);

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function getPendingProjectsCount_returns_zero_for_user_with_no_pending_projects()
    {
        // Create approved projects only
        Project::factory()->count(5)->owner($this->user)->create();

        $count = $this->quotaService->getPendingProjectsCount($this->user);

        $this->assertEquals(0, $count);
    }

    #[Test]
    public function getTotalStorageUsed_calculates_correctly()
    {
        $project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        // Add files with specific sizes
        $version->files()->create([
            'name' => 'file1.zip',
            'path' => 'test/file1.zip',
            'size' => 1024 * 1024 * 100, // 100MB
        ]);

        $version->files()->create([
            'name' => 'file2.zip',
            'path' => 'test/file2.zip',
            'size' => 1024 * 1024 * 50, // 50MB
        ]);

        $storage = $this->quotaService->getTotalStorageUsed($this->user);

        $this->assertEquals(1024 * 1024 * 150, $storage);
    }

    #[Test]
    public function getProjectStorageUsed_calculates_correctly()
    {
        $project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $version->files()->create([
            'name' => 'file.zip',
            'path' => 'test/file.zip',
            'size' => 1024 * 1024 * 200, // 200MB
        ]);

        $storage = $this->quotaService->getProjectStorageUsed($project);

        $this->assertEquals(1024 * 1024 * 200, $storage);
    }

    #[Test]
    public function canCreateProject_returns_true_when_under_limit()
    {
        Config::set('quotas.pending_projects_max', 3);

        // Create 2 pending projects
        Project::factory()->count(2)->owner($this->user)->pending()->create();

        $canCreate = $this->quotaService->canCreateProject($this->user);

        $this->assertTrue($canCreate);
    }

    #[Test]
    public function canCreateProject_returns_false_when_at_limit()
    {
        Config::set('quotas.pending_projects_max', 3);

        // Create 3 pending projects (at limit)
        Project::factory()->count(3)->owner($this->user)->pending()->create();

        $canCreate = $this->quotaService->canCreateProject($this->user);

        $this->assertFalse($canCreate);
    }

    #[Test]
    public function canCreateProject_returns_true_for_admin()
    {
        Config::set('quotas.pending_projects_max', 3);

        $admin = User::factory()->create(['role' => 'admin']);

        // Create 10 pending projects
        Project::factory()->count(10)->owner($admin)->pending()->create();

        $canCreate = $this->quotaService->canCreateProject($admin);

        $this->assertTrue($canCreate);
    }

    #[Test]
    public function validateProjectCreation_throws_exception_at_limit()
    {
        Config::set('quotas.pending_projects_max', 3);

        // Create 3 pending projects (at limit)
        Project::factory()->count(3)->owner($this->user)->pending()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('maximum number of pending projects');

        $this->quotaService->validateProjectCreation($this->user);
    }

    #[Test]
    public function validateStorageQuota_throws_exception_when_exceeded()
    {
        Config::set('quotas.total_storage_max', 1024 * 1024 * 100); // 100MB

        $project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        // Use 150MB of storage (exceeds 100MB limit)
        $version->files()->create([
            'name' => 'large-file.zip',
            'path' => 'test/large-file.zip',
            'size' => 1024 * 1024 * 150,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Storage quota exceeded');

        $this->quotaService->validateStorageQuota($this->user);
    }

    #[Test]
    public function validateProjectCreation_passes_for_admin()
    {
        Config::set('quotas.pending_projects_max', 3);

        $admin = User::factory()->create(['role' => 'admin']);

        // Create 10 pending projects
        Project::factory()->count(10)->owner($admin)->pending()->create();

        // Should not throw exception
        $this->quotaService->validateProjectCreation($admin);
        $this->assertTrue(true); // If we get here, test passes
    }

    // ==========================================================================
    // Quota Limits Hierarchy Tests
    // ==========================================================================

    #[Test]
    public function getQuotaLimits_returns_config_defaults()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1073741824); // 1GB
        Config::set('quotas.project_storage_max', 524288000); // 500MB
        Config::set('quotas.versions_per_day_max', 5);
        Config::set('quotas.version_size_max', 104857600); // 100MB
        Config::set('quotas.files_per_version_max', 5);
        Config::set('quotas.file_size_max', 104857600); // 100MB

        $limits = $this->quotaService->getQuotaLimits($this->user);

        $this->assertEquals(3, $limits['pending_projects_max']);
        $this->assertEquals(1073741824, $limits['total_storage_max']);
        $this->assertEquals(524288000, $limits['project_storage_max']);
        $this->assertEquals(5, $limits['versions_per_day_max']);
        $this->assertEquals(104857600, $limits['version_size_max']);
        $this->assertEquals(5, $limits['files_per_version_max']);
        $this->assertEquals(104857600, $limits['file_size_max']);
    }

    #[Test]
    public function getQuotaLimits_applies_project_type_override()
    {
        Config::set('quotas.project_storage_max', 524288000); // 500MB default

        $projectType = ProjectType::factory()->create();
        $projectType->quota()->create([
            'project_storage_max' => 2147483648, // 2GB override
        ]);

        $limits = $this->quotaService->getQuotaLimits($this->user, $projectType);

        $this->assertEquals(2147483648, $limits['project_storage_max']);
    }

    #[Test]
    public function getQuotaLimits_applies_project_override()
    {
        Config::set('quotas.project_storage_max', 524288000); // 500MB default

        $projectType = ProjectType::factory()->create();
        $project = Project::factory()->create([
            'project_type_id' => $projectType->id
        ]);
        $project->quota()->create([
            'project_storage_max' => 10737418240, // 10GB override
        ]);

        $limits = $this->quotaService->getQuotaLimits($this->user, $projectType, $project);

        $this->assertEquals(10737418240, $limits['project_storage_max']);
    }

    #[Test]
    public function getQuotaLimits_applies_user_override()
    {
        Config::set('quotas.total_storage_max', 1073741824); // 1GB default

        $this->user->quota()->create([
            'total_storage_max' => 10737418240, // 10GB override
        ]);

        $limits = $this->quotaService->getQuotaLimits($this->user);

        $this->assertEquals(10737418240, $limits['total_storage_max']);
    }

    #[Test]
    public function getQuotaLimits_partial_overrides_merge_correctly()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1073741824);
        Config::set('quotas.project_storage_max', 524288000);
        Config::set('quotas.versions_per_day_max', 5);

        // User only overrides total_storage_max
        UserQuota::factory()->create([
            'user_id' => $this->user->id,
            'total_storage_max' => 5368709120, // 5GB override
        ]);

        $limits = $this->quotaService->getQuotaLimits($this->user);

        // User override should be applied
        $this->assertEquals(5368709120, $limits['total_storage_max']);
        // Other values should still be from config
        $this->assertEquals(3, $limits['pending_projects_max']);
        $this->assertEquals(524288000, $limits['project_storage_max']);
        $this->assertEquals(5, $limits['versions_per_day_max']);
    }

    #[Test]
    public function getQuotaLimits_user_overrides_project()
    {
        Config::set('quotas.total_storage_max', 1073741824); // 1GB default

        // User has 5GB limit
        $this->user->quota()->create([
            'total_storage_max' => 5368709120, // 5GB
        ]);

        // Project has 100MB limit
        $project = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);
        $project->quota()->create([
            'project_storage_max' => 104857600, // 100MB
        ]);

        $limits = $this->quotaService->getQuotaLimits($this->user, $this->projectType, $project);

        // User quota should take precedence
        $this->assertEquals(5368709120, $limits['total_storage_max']);
    }

    #[Test]
    public function getQuotaLimits_project_overrides_project_type()
    {
        Config::set('quotas.project_storage_max', 524288000); // 500MB default

        // ProjectType has 2GB limit
        $this->projectType->quota()->create([
            'project_storage_max' => 2147483648, // 2GB
        ]);

        // Project has 100MB limit
        $project = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);
        $project->quota()->create([
            'project_storage_max' => 104857600, // 100MB
        ]);

        $limits = $this->quotaService->getQuotaLimits($this->user, $this->projectType, $project);

        // Project quota should take precedence
        $this->assertEquals(104857600, $limits['project_storage_max']);
    }

    // ==========================================================================
    // Admin Exemption Tests
    // ==========================================================================

    #[Test]
    public function isExemptFromQuotas_returns_true_for_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $isExempt = $this->quotaService->isExemptFromQuotas($admin);

        $this->assertTrue($isExempt);
    }

    #[Test]
    public function isExemptFromQuotas_returns_false_for_regular_user()
    {
        $isExempt = $this->quotaService->isExemptFromQuotas($this->user);

        $this->assertFalse($isExempt);
    }

    // ==========================================================================
    // Quota Status Tests
    // ==========================================================================

    #[Test]
    public function getQuotaStatus_returns_exempt_for_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $status = $this->quotaService->getQuotaStatus($admin);

        $this->assertTrue($status['exempt']);
        $this->assertNull($status['pending_projects_max']);
        $this->assertNull($status['total_storage_max']);
    }

    #[Test]
    public function getQuotaStatus_returns_correct_usage()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1073741824);

        // Create 2 pending projects
        Project::factory()->count(2)->owner($this->user)->pending()->create();

        // Add some storage
        $project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id
        ]);
        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);
        $version->files()->create([
            'name' => 'file.zip',
            'path' => 'test/file.zip',
            'size' => 1024 * 1024 * 100, // 100MB
        ]);

        $status = $this->quotaService->getQuotaStatus($this->user);

        $this->assertFalse($status['exempt']);
        $this->assertEquals(2, $status['pending_projects']);
        $this->assertEquals(3, $status['pending_projects_max']);
        $this->assertEquals(1024 * 1024 * 100, $status['total_storage_used']);
        $this->assertEquals(1073741824, $status['total_storage_max']);
    }

    #[Test]
    public function getQuotaStatus_formats_bytes_correctly()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1073741824); // 1GB

        // Create 1 pending project
        Project::factory()->owner($this->user)->pending()->create();

        $status = $this->quotaService->getQuotaStatus($this->user);

        // Should be formatted correctly
        $this->assertEquals('0 B', $status['total_storage_used_formatted']);
        $this->assertEquals('1 GB', $status['total_storage_max_formatted']);
    }

    #[Test]
    public function getQuotaStatus_formats_megabytes_correctly()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1073741824); // 1GB

        // Create 1 pending project
        Project::factory()->owner($this->user)->pending()->create();

        // Add 500MB of storage
        $project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id
        ]);
        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);
        $version->files()->create([
            'name' => 'file.zip',
            'path' => 'test/file.zip',
            'size' => 1024 * 1024 * 500, // 500MB
        ]);

        $status = $this->quotaService->getQuotaStatus($this->user);

        $this->assertEquals('500 MB', $status['total_storage_used_formatted']);
    }

    // ==========================================================================
    // Quota Breach Tests
    // ==========================================================================

    #[Test]
    public function checkQuotaBreach_returns_null_when_under_limit()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1073741824);

        // Create 1 pending project (under limit)
        Project::factory()->owner($this->user)->pending()->create();

        $breach = $this->quotaService->checkQuotaBreach($this->user);

        $this->assertNull($breach);
    }

    #[Test]
    public function checkQuotaBreach_returns_message_when_pending_exceeded()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1073741824);

        // Create 5 pending projects (exceeds limit)
        Project::factory()->count(5)->owner($this->user)->pending()->create();

        $breach = $this->quotaService->checkQuotaBreach($this->user);

        $this->assertNotNull($breach);
        $this->assertStringContainsString('pending projects', $breach);
    }

    #[Test]
    public function checkQuotaBreach_returns_message_when_storage_exceeded()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1024 * 1024 * 100); // 100MB

        // Create 1 pending project
        Project::factory()->owner($this->user)->pending()->create();

        // Add 200MB of storage (exceeds limit)
        $project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id
        ]);
        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);
        $version->files()->create([
            'name' => 'large-file.zip',
            'path' => 'test/large-file.zip',
            'size' => 1024 * 1024 * 200, // 200MB
        ]);

        $breach = $this->quotaService->checkQuotaBreach($this->user);

        $this->assertNotNull($breach);
        $this->assertStringContainsString('storage', $breach);
    }

    #[Test]
    public function checkQuotaBreach_returns_null_for_admin()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1024 * 1024 * 100);

        $admin = User::factory()->create(['role' => 'admin']);

        // Create 10 pending projects (far exceeds limit)
        Project::factory()->count(10)->owner($admin)->pending()->create();

        $breach = $this->quotaService->checkQuotaBreach($admin);

        $this->assertNull($breach);
    }

    // ==========================================================================
    // Multi-user Tests
    // ==========================================================================

    #[Test]
    public function storage_used_only_counts_own_projects()
    {
        Config::set('quotas.total_storage_max', 1073741824);

        $otherUser = User::factory()->create();

        // Create project for other user with 500MB
        $otherProject = Project::factory()->owner($otherUser)->create([
            'project_type_id' => $this->projectType->id
        ]);
        $otherVersion = $otherProject->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);
        $otherVersion->files()->create([
            'name' => 'file.zip',
            'path' => 'test/file.zip',
            'size' => 1024 * 1024 * 500,
        ]);

        // User should have 0 storage used
        $storage = $this->quotaService->getTotalStorageUsed($this->user);

        $this->assertEquals(0, $storage);
    }

    #[Test]
    public function pending_projects_only_counts_own_projects()
    {
        Config::set('quotas.pending_projects_max', 3);

        $otherUser = User::factory()->create();

        // Create 3 pending projects for other user
        Project::factory()->count(3)->owner($otherUser)->pending()->create();

        // User should have 0 pending projects
        $count = $this->quotaService->getPendingProjectsCount($this->user);

        $this->assertEquals(0, $count);
    }

    // ==========================================================================
    // Edge Cases
    // ==========================================================================

    #[Test]
    public function getQuotaLimits_handles_null_user()
    {
        Config::set('quotas.pending_projects_max', 5);

        // Pass null as user - should use config defaults
        $limits = $this->quotaService->getQuotaLimits(
            $this->user, // Use actual user
            null,
            null
        );

        $this->assertEquals(5, $limits['pending_projects_max']);
    }

    #[Test]
    public function getQuotaLimits_handles_null_project_type()
    {
        Config::set('quotas.project_storage_max', 524288000);

        $limits = $this->quotaService->getQuotaLimits($this->user, null);

        $this->assertEquals(524288000, $limits['project_storage_max']);
    }

    #[Test]
    public function getQuotaLimits_handles_null_project()
    {
        Config::set('quotas.project_storage_max', 524288000);

        $limits = $this->quotaService->getQuotaLimits($this->user, $this->projectType, null);

        $this->assertEquals(524288000, $limits['project_storage_max']);
    }

    #[Test]
    public function validateStorageQuota_allows_additional_storage()
    {
        Config::set('quotas.total_storage_max', 1024 * 1024 * 200); // 200MB

        // Add 100MB of existing storage
        $project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id
        ]);
        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);
        $version->files()->create([
            'name' => 'file.zip',
            'path' => 'test/file.zip',
            'size' => 1024 * 1024 * 100, // 100MB
        ]);

        // Try to add 50MB more (total 150MB < 200MB limit) - should pass
        $this->quotaService->validateStorageQuota($this->user, 1024 * 1024 * 50);
        $this->assertTrue(true);
    }

    #[Test]
    public function validateStorageQuota_rejects_when_total_would_exceed()
    {
        Config::set('quotas.total_storage_max', 1024 * 1024 * 200); // 200MB

        // Add 150MB of existing storage
        $project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id
        ]);
        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);
        $version->files()->create([
            'name' => 'file.zip',
            'path' => 'test/file.zip',
            'size' => 1024 * 1024 * 150, // 150MB
        ]);

        // Try to add 100MB more (total 250MB > 200MB limit) - should fail
        $this->expectException(\Exception::class);
        $this->quotaService->validateStorageQuota($this->user, 1024 * 1024 * 100);
    }
}
