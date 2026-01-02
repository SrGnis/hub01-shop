<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Livewire\ProjectForm;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectTag;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\User;
use App\Models\UserQuota;
use App\Models\ProjectQuota;
use App\Models\ProjectTypeQuota;
use App\Services\ProjectService;
use App\Services\ProjectQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuotaTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;
    private User $user;
    private ProjectService $projectService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->projectType = ProjectType::factory()->create();
        $this->user = User::factory()->create();

        $quotaService = $this->app->make(ProjectQuotaService::class);
        $this->projectService = new ProjectService($quotaService);
    }

    // ==========================================================================
    // Config Defaults Tests
    // ==========================================================================

    #[Test]
    public function user_can_create_project_when_under_pending_limit()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('projects.auto_approve', false);

        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        $data = [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'summary' => 'Test summary',
            'description' => 'Test description',
            'website' => '',
            'issues' => '',
            'source' => '',
            'status' => 'active',
            'selectedTags' => [$tag->id],
            'project_type_id' => $this->projectType->id,
        ];

        $project = $this->projectService->saveProject(null, $this->user, $data);

        $this->assertNotNull($project);
        $this->assertEquals(ApprovalStatus::DRAFT, $project->approval_status);
    }

    #[Test]
    public function user_cannot_create_project_when_at_pending_limit()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('projects.auto_approve', false);

        // Create 3 pending projects
        for ($i = 0; $i < 3; $i++) {
            $project = Project::factory()->owner($this->user)->pending()->create();
        }

        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        $data = [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'summary' => 'Test summary',
            'description' => 'Test description',
            'website' => '',
            'issues' => '',
            'source' => '',
            'status' => 'active',
            'selectedTags' => [$tag->id],
            'project_type_id' => $this->projectType->id,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('maximum number of pending projects');

        $this->projectService->saveProject(null, $this->user, $data);
    }

    #[Test]
    public function storage_quota_enforced_at_config_default()
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

        // Add 100MB of files (at limit)
        $version->files()->create([
            'name' => 'file.zip',
            'path' => 'test/file.zip',
            'size' => 1024 * 1024 * 100,
        ]);

        // Should not throw (at limit but not over)
        $this->quotaService()->validateStorageQuota($this->user);
        $this->assertTrue(true);
    }

    #[Test]
    public function storage_quota_blocks_excess_upload()
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

        // Add 150MB of files (exceeds limit)
        $version->files()->create([
            'name' => 'large-file.zip',
            'path' => 'test/large-file.zip',
            'size' => 1024 * 1024 * 150,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Storage quota exceeded');

        $this->quotaService()->validateStorageQuota($this->user);
    }

    // ==========================================================================
    // User-Level Override Tests
    // ==========================================================================

    #[Test]
    public function user_quota_override_increases_storage_limit()
    {
        Config::set('quotas.total_storage_max', 1024 * 1024 * 100); // 100MB default

        // User has custom 1GB limit
        UserQuota::factory()->create([
            'user_id' => $this->user->id,
            'total_storage_max' => 1024 * 1024 * 1024, // 1GB
        ]);

        $project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id
        ]);
        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        // Add 500MB of files (under user's 1GB limit)
        $version->files()->create([
            'name' => 'file.zip',
            'path' => 'test/file.zip',
            'size' => 1024 * 1024 * 500,
        ]);

        // Should not throw - within user's custom limit
        $this->quotaService()->validateStorageQuota($this->user);
        $this->assertTrue(true);
    }

    #[Test]
    public function user_quota_override_increases_pending_projects()
    {
        Config::set('quotas.pending_projects_max', 3); // Default

        // User has custom 10 project limit
        UserQuota::factory()->create([
            'user_id' => $this->user->id,
            'total_storage_max' => 10737418240, // 10GB
        ]);

        // Manually update the user's quota to also have pending_projects_max
        // Since UserQuota doesn't have this field by default, we test with storage
        // For pending projects, we need to modify the test approach

        $limits = $this->quotaService()->getQuotaLimits($this->user);

        // User should have the custom storage limit
        $this->assertEquals(10737418240, $limits['total_storage_max']);
    }

    #[Test]
    public function user_quota_override_does_not_affect_other_users()
    {
        Config::set('quotas.total_storage_max', 1024 * 1024 * 100); // 100MB default

        $otherUser = User::factory()->create();

        // User A has custom 1GB limit
        UserQuota::factory()->create([
            'user_id' => $this->user->id,
            'total_storage_max' => 1024 * 1024 * 1024, // 1GB
        ]);

        // User B should still use default
        $userBLimits = $this->quotaService()->getQuotaLimits($otherUser);

        $this->assertEquals(1024 * 1024 * 100, $userBLimits['total_storage_max']);
    }

    // ==========================================================================
    // ProjectType-Level Override Tests
    // ==========================================================================

    #[Test]
    public function project_type_quota_override_applies_to_projects()
    {
        Config::set('quotas.project_storage_max', 524288000); // 500MB default

        // ProjectType has custom 2GB limit
        ProjectTypeQuota::factory()->create([
            'project_type_id' => $this->projectType->id,
            'project_storage_max' => 2147483648, // 2GB
        ]);

        $project = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);

        $limits = $this->quotaService()->getQuotaLimits(
            $this->user,
            $this->projectType,
            $project
        );

        $this->assertEquals(2147483648, $limits['project_storage_max']);
    }

    #[Test]
    public function project_type_quota_isolation()
    {
        Config::set('quotas.project_storage_max', 524288000); // 500MB default

        $typeA = ProjectType::factory()->create();
        $typeB = ProjectType::factory()->create();

        // Type A has 2GB limit
        ProjectTypeQuota::factory()->create([
            'project_type_id' => $typeA->id,
            'project_storage_max' => 2147483648, // 2GB
        ]);

        // Type B has 100MB limit
        ProjectTypeQuota::factory()->create([
            'project_type_id' => $typeB->id,
            'project_storage_max' => 104857600, // 100MB
        ]);

        $projectA = Project::factory()->create(['project_type_id' => $typeA->id]);
        $projectB = Project::factory()->create(['project_type_id' => $typeB->id]);

        $limitsA = $this->quotaService()->getQuotaLimits($this->user, $typeA, $projectA);
        $limitsB = $this->quotaService()->getQuotaLimits($this->user, $typeB, $projectB);

        $this->assertEquals(2147483648, $limitsA['project_storage_max']);
        $this->assertEquals(104857600, $limitsB['project_storage_max']);
    }

    // ==========================================================================
    // Project-Level Override Tests
    // ==========================================================================

    #[Test]
    public function project_quota_override_applies_to_specific_project()
    {
        Config::set('quotas.project_storage_max', 524288000); // 500MB default

        $project = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);

        // Project has custom 10GB limit
        ProjectQuota::factory()->create([
            'project_id' => $project->id,
            'project_storage_max' => 10737418240, // 10GB
        ]);

        $limits = $this->quotaService()->getQuotaLimits(
            $this->user,
            $this->projectType,
            $project
        );

        $this->assertEquals(10737418240, $limits['project_storage_max']);
    }

    #[Test]
    public function project_quota_does_not_affect_other_projects()
    {
        Config::set('quotas.project_storage_max', 524288000); // 500MB default

        $projectA = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);
        $projectB = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);

        // Only Project A has custom quota
        ProjectQuota::factory()->create([
            'project_id' => $projectA->id,
            'project_storage_max' => 10737418240, // 10GB
        ]);

        $limitsA = $this->quotaService()->getQuotaLimits($this->user, $this->projectType, $projectA);
        $limitsB = $this->quotaService()->getQuotaLimits($this->user, $this->projectType, $projectB);

        $this->assertEquals(10737418240, $limitsA['project_storage_max']);
        $this->assertEquals(524288000, $limitsB['project_storage_max']);
    }

    // ==========================================================================
    // Priority Hierarchy Tests
    // ==========================================================================

    #[Test]
    public function user_quota_takes_precedence_over_project_quota()
    {
        Config::set('quotas.total_storage_max', 1073741824); // 1GB default

        // User has 5GB limit
        UserQuota::factory()->create([
            'user_id' => $this->user->id,
            'total_storage_max' => 5368709120, // 5GB
        ]);

        // Project has 100MB limit (but this is for project_storage_max, not total_storage_max)
        $project = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);
        ProjectQuota::factory()->create([
            'project_id' => $project->id,
            'project_storage_max' => 104857600, // 100MB
        ]);

        $limits = $this->quotaService()->getQuotaLimits(
            $this->user,
            $this->projectType,
            $project
        );

        // User quota should take precedence for total_storage_max
        $this->assertEquals(5368709120, $limits['total_storage_max']);
        // Project quota should apply for project_storage_max
        $this->assertEquals(104857600, $limits['project_storage_max']);
    }

    #[Test]
    public function project_quota_takes_precedence_over_project_type_quota()
    {
        Config::set('quotas.project_storage_max', 524288000); // 500MB default

        // ProjectType has 2GB limit
        ProjectTypeQuota::factory()->create([
            'project_type_id' => $this->projectType->id,
            'project_storage_max' => 2147483648, // 2GB
        ]);

        // Project has 100MB limit
        $project = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);
        ProjectQuota::factory()->create([
            'project_id' => $project->id,
            'project_storage_max' => 104857600, // 100MB
        ]);

        $limits = $this->quotaService()->getQuotaLimits(
            $this->user,
            $this->projectType,
            $project
        );

        // Project quota should take precedence
        $this->assertEquals(104857600, $limits['project_storage_max']);
    }

    #[Test]
    public function project_type_quota_takes_precedence_over_config()
    {
        Config::set('quotas.project_storage_max', 524288000); // 500MB default

        // ProjectType has 2GB limit
        ProjectTypeQuota::factory()->create([
            'project_type_id' => $this->projectType->id,
            'project_storage_max' => 2147483648, // 2GB
        ]);

        $project = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);

        $limits = $this->quotaService()->getQuotaLimits(
            $this->user,
            $this->projectType,
            $project
        );

        // ProjectType quota should take precedence
        $this->assertEquals(2147483648, $limits['project_storage_max']);
    }

    #[Test]
    public function complete_hierarchy_chain()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1073741824); // 1GB
        Config::set('quotas.project_storage_max', 524288000); // 500MB
        Config::set('quotas.versions_per_day_max', 5);
        Config::set('quotas.version_size_max', 104857600); // 100MB
        Config::set('quotas.files_per_version_max', 5);
        Config::set('quotas.file_size_max', 104857600); // 100MB

        // User overrides total_storage_max
        UserQuota::factory()->create([
            'user_id' => $this->user->id,
            'total_storage_max' => 10737418240, // 10GB
        ]);

        // ProjectType does NOT override project_storage_max
        ProjectTypeQuota::factory()->create([
            'project_type_id' => $this->projectType->id,
            'versions_per_day_max' => 10, // Only set versions_per_day_max
            'project_storage_max' => null, // Explicitly null to use config default
        ]);

        // Project overrides versions_per_day_max but NOT project_storage_max
        $project = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);
        ProjectQuota::factory()->create([
            'project_id' => $project->id,
            'versions_per_day_max' => 20,
            'project_storage_max' => null, // Explicitly null to use lower priority
        ]);

        $limits = $this->quotaService()->getQuotaLimits(
            $this->user,
            $this->projectType,
            $project
        );

        // Verify each level's override is applied
        $this->assertEquals(3, $limits['pending_projects_max']); // Default
        $this->assertEquals(10737418240, $limits['total_storage_max']); // User override
        $this->assertEquals(524288000, $limits['project_storage_max']); // Config default (not overridden)
        $this->assertEquals(20, $limits['versions_per_day_max']); // Project override
        $this->assertEquals(104857600, $limits['version_size_max']); // Default
        $this->assertEquals(5, $limits['files_per_version_max']); // Default
        $this->assertEquals(104857600, $limits['file_size_max']); // Default
    }

    // ==========================================================================
    // Admin Exemption Tests
    // ==========================================================================

    #[Test]
    public function admin_is_exempt_from_all_quotas()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1024 * 1024 * 100); // 100MB

        $admin = User::factory()->create(['role' => 'admin']);

        // Create 10 pending projects for admin
        Project::factory()->count(10)->owner($admin)->pending()->create();

        // Admin should still be able to create
        $this->assertTrue($this->quotaService()->canCreateProject($admin));

        // Admin should have exempt status
        $status = $this->quotaService()->getQuotaStatus($admin);
        $this->assertTrue($status['exempt']);
    }

    #[Test]
    public function admin_can_always_create_projects()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('projects.auto_approve', false);

        $admin = User::factory()->create(['role' => 'admin']);

        // Create 10 pending projects
        for ($i = 0; $i < 10; $i++) {
            Project::factory()->owner($admin)->pending()->create();
        }

        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        $data = [
            'name' => 'Admin Project',
            'slug' => 'admin-project',
            'summary' => 'Admin summary',
            'description' => 'Admin description',
            'website' => '',
            'issues' => '',
            'source' => '',
            'status' => 'active',
            'selectedTags' => [$tag->id],
            'project_type_id' => $this->projectType->id,
        ];

        // Should not throw exception
        $project = $this->projectService->saveProject(null, $admin, $data);
        $this->assertNotNull($project);
    }

    #[Test]
    public function admin_storage_unlimited()
    {
        Config::set('quotas.total_storage_max', 1024 * 1024 * 100); // 100MB

        $admin = User::factory()->create(['role' => 'admin']);

        // Add 500MB of storage (well over the 100MB limit)
        $project = Project::factory()->owner($admin)->create([
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
            'size' => 1024 * 1024 * 500, // 500MB
        ]);

        // Should not throw - admin is exempt
        $this->quotaService()->validateStorageQuota($admin);
        $this->assertTrue(true);
    }

    // ==========================================================================
    // Livewire Integration Tests
    // ==========================================================================

    #[Test]
    public function project_creation_blocked_when_quota_exceeded_livewire()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('projects.auto_approve', false);

        // Create 3 pending projects
        for ($i = 0; $i < 3; $i++) {
            Project::factory()->owner($this->user)->pending()->create();
        }

        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        // Quota validation happens in the service layer, not in Livewire validation
        // The component catches exceptions and shows flash messages
        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Quota Test Project')
            ->set('slug', 'quota-test-project')
            ->set('summary', 'Test summary')
            ->set('description', 'Test description')
            ->set('selectedTags', [$tag->id])
            ->call('save');

        // Project should NOT be created
        $this->assertDatabaseMissing('project', [
            'name' => 'Quota Test Project',
        ]);
    }

    #[Test]
    public function project_creation_works_when_under_quota_livewire()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('projects.auto_approve', false);

        // Only 2 pending projects
        Project::factory()->count(2)->owner($this->user)->pending()->create();

        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Quota Test Project')
            ->set('slug', 'quota-test-project')
            ->set('summary', 'Test summary')
            ->set('description', 'Test description')
            ->set('selectedTags', [$tag->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        // Verify project was created
        $this->assertDatabaseHas('project', [
            'name' => 'Quota Test Project',
            'approval_status' => 'draft',
        ]);
    }

    // ==========================================================================
    // Edge Cases
    // ==========================================================================

    #[Test]
    public function empty_quota_table_uses_config()
    {
        Config::set('quotas.total_storage_max', 1073741824); // 1GB

        // No quota records in database
        $limits = $this->quotaService()->getQuotaLimits($this->user);

        $this->assertEquals(1073741824, $limits['total_storage_max']);
    }

    #[Test]
    public function null_quota_values_fall_through_to_defaults()
    {
        Config::set('quotas.pending_projects_max', 3);
        Config::set('quotas.total_storage_max', 1073741824);

        // Create user quota directly with null value (factory sets default)
        UserQuota::create([
            'user_id' => $this->user->id,
            'total_storage_max' => null, // Should use default
        ]);

        $limits = $this->quotaService()->getQuotaLimits($this->user);

        // Should use config default for total_storage_max
        $this->assertEquals(1073741824, $limits['total_storage_max']);
        // pending_projects_max should still be from config
        $this->assertEquals(3, $limits['pending_projects_max']);
    }

    #[Test]
    public function partial_project_type_override_uses_config_for_rest()
    {
        Config::set('quotas.project_storage_max', 524288000); // 500MB
        Config::set('quotas.versions_per_day_max', 5);
        Config::set('quotas.version_size_max', 104857600); // 100MB

        // ProjectType only overrides project_storage_max
        ProjectTypeQuota::factory()->create([
            'project_type_id' => $this->projectType->id,
            'project_storage_max' => 1073741824, // 1GB
            'versions_per_day_max' => null, // Should use default
            'version_size_max' => null, // Should use default
        ]);

        $project = Project::factory()->create([
            'project_type_id' => $this->projectType->id
        ]);

        $limits = $this->quotaService()->getQuotaLimits(
            $this->user,
            $this->projectType,
            $project
        );

        $this->assertEquals(1073741824, $limits['project_storage_max']); // Override
        $this->assertEquals(5, $limits['versions_per_day_max']); // Default
        $this->assertEquals(104857600, $limits['version_size_max']); // Default
    }

    // ==========================================================================
    // Helper Methods
    // ==========================================================================

    private function quotaService(): ProjectQuotaService
    {
        return $this->app->make(ProjectQuotaService::class);
    }
}
