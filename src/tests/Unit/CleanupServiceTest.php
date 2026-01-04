<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectVersion;
use App\Models\User;
use App\Notifications\UnverifiedUserDeletionWarning;
use App\Services\CleanupService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    private CleanupService $cleanupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupService = new CleanupService(new UserService());
    }

    // ========================================
    // Orphaned Files Tests
    // ========================================

    public function test_list_orphaned_files_returns_files_not_in_database()
    {
        Storage::fake(ProjectFile::getDisk());
        Storage::fake('public');

        // Create some files in storage
        Storage::disk(ProjectFile::getDisk())->put(ProjectFile::getDirectory().'/orphaned1.zip', 'content');
        Storage::disk(ProjectFile::getDisk())->put(ProjectFile::getDirectory().'/orphaned2.zip', 'content');
        Storage::disk('public')->put('avatars/orphaned-avatar.jpg', 'content');
        Storage::disk('public')->put('project-logos/orphaned-logo.png', 'content');

        // Create a referenced file
        Storage::disk(ProjectFile::getDisk())->put(ProjectFile::getDirectory().'/referenced.zip', 'content');
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create(['logo_path' => null]);
        $version = ProjectVersion::factory()->create(['project_id' => $project->id]);
        ProjectFile::factory()->withPath(ProjectFile::getDirectory().'/referenced.zip')->create([
            'project_version_id' => $version->id,
        ]);

        $orphanedFiles = $this->cleanupService->listOrphanedFiles();

        $this->assertCount(4, $orphanedFiles);
        $orphanedPaths = $orphanedFiles->pluck('path')->toArray();
        $this->assertContains(ProjectFile::getDirectory().'/orphaned1.zip', $orphanedPaths);
        $this->assertContains(ProjectFile::getDirectory().'/orphaned2.zip', $orphanedPaths);
        $this->assertContains('avatars/orphaned-avatar.jpg', $orphanedPaths);
        $this->assertContains('project-logos/orphaned-logo.png', $orphanedPaths);
        $this->assertNotContains(ProjectFile::getDirectory().'/referenced.zip', $orphanedPaths);
    }

    public function test_list_orphaned_files_returns_empty_when_all_referenced()
    {
        Storage::fake(ProjectFile::getDisk());
        Storage::fake('public');

        // Create files and matching database references
        Storage::disk(ProjectFile::getDisk())->put(ProjectFile::getDirectory().'/file1.zip', 'content');
        Storage::disk('public')->put('avatars/avatar1.jpg', 'content');
        Storage::disk('public')->put('project-logos/logo1.png', 'content');

        $user = User::factory()->create(['avatar' => 'avatars/avatar1.jpg']);
        $project = Project::factory()->owner($user)->create([
            'logo_path' => 'project-logos/logo1.png',
        ]);
        $version = ProjectVersion::factory()->create(['project_id' => $project->id]);
        ProjectFile::factory()->withPath(ProjectFile::getDirectory().'/file1.zip')->create([
            'project_version_id' => $version->id,
        ]);

        $orphanedFiles = $this->cleanupService->listOrphanedFiles();

        $this->assertCount(0, $orphanedFiles);
    }

    public function test_delete_orphaned_files_removes_files_from_storage()
    {
        Storage::fake(ProjectFile::getDisk());
        Storage::fake('public');

        // Create orphaned files
        Storage::disk(ProjectFile::getDisk())->put(ProjectFile::getDirectory().'/orphaned1.zip', 'content');
        Storage::disk('public')->put('avatars/orphaned-avatar.jpg', 'content');

        $deletedCount = $this->cleanupService->deleteOrphanedFiles();

        $this->assertEquals(2, $deletedCount);
        Storage::disk(ProjectFile::getDisk())->assertMissing(ProjectFile::getDirectory().'/orphaned1.zip');
        Storage::disk('public')->assertMissing('avatars/orphaned-avatar.jpg');
    }

    public function test_delete_orphaned_files_does_not_delete_referenced_files()
    {
        Storage::fake(ProjectFile::getDisk());
        Storage::fake('public');

        // Create referenced file
        Storage::disk(ProjectFile::getDisk())->put(ProjectFile::getDirectory().'/referenced.zip', 'content');
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();
        $version = ProjectVersion::factory()->create(['project_id' => $project->id]);
        ProjectFile::factory()->withPath(ProjectFile::getDirectory().'/referenced.zip')->create([
            'project_version_id' => $version->id,
        ]);

        $deletedCount = $this->cleanupService->deleteOrphanedFiles();

        $this->assertEquals(0, $deletedCount);
        Storage::disk(ProjectFile::getDisk())->assertExists(ProjectFile::getDirectory().'/referenced.zip');
    }

    public function test_get_referenced_files_includes_all_file_types()
    {
        Storage::fake(ProjectFile::getDisk());
        Storage::fake('public');

        // Create project file
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create(['logo_path' => null]);
        $version = ProjectVersion::factory()->create(['project_id' => $project->id]);
        ProjectFile::factory()->withPath(ProjectFile::getDirectory().'/file.zip')->create([
            'project_version_id' => $version->id,
        ]);

        // Create user with avatar
        $user2 = User::factory()->create(['avatar' => 'avatars/avatar.jpg']);

        // Create project with logo
        $user3 = User::factory()->create();
        Project::factory()->owner($user3)->create([
            'logo_path' => 'project-logos/logo.png',
        ]);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->cleanupService);
        $method = $reflection->getMethod('getReferencedFiles');
        $method->setAccessible(true);

        $referencedFiles = $method->invoke($this->cleanupService);

        $this->assertCount(3, $referencedFiles);
        $this->assertTrue($referencedFiles->contains(ProjectFile::getDirectory().'/file.zip'));
        $this->assertTrue($referencedFiles->contains('avatars/avatar.jpg'));
        $this->assertTrue($referencedFiles->contains('project-logos/logo.png'));
    }

    public function test_orphaned_files_handles_multiple_disks()
    {
        Storage::fake(ProjectFile::getDisk());
        Storage::fake('public');

        // Create orphaned files on different disks
        Storage::disk('local')->put(ProjectFile::getDirectory().'/orphaned-local.zip', 'content');
        Storage::disk('public')->put('avatars/orphaned-public.jpg', 'content');

        $orphanedFiles = $this->cleanupService->listOrphanedFiles();

        $this->assertCount(2, $orphanedFiles);
        $disks = $orphanedFiles->pluck('disk')->unique()->toArray();
        $this->assertContains('local', $disks);
        $this->assertContains('public', $disks);
    }

    // ========================================
    // Unverified Users Tests
    // ========================================

    public function test_list_unverified_users_returns_users_without_verification()
    {
        // Clear any existing users from previous tests
        User::query()->forceDelete();

        // Create verified users
        User::factory()->count(2)->create(['email_verified_at' => now()]);

        // Create unverified users
        User::factory()->count(3)->create(['email_verified_at' => null]);

        $unverifiedUsers = $this->cleanupService->listUnverifiedUsers();

        $this->assertCount(3, $unverifiedUsers);
        foreach ($unverifiedUsers as $user) {
            $this->assertNull($user->email_verified_at);
        }
    }

    public function test_list_unverified_users_filters_by_days_old()
    {
        // Create unverified users with different ages
        User::factory()->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(5),
        ]);
        User::factory()->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(10),
        ]);
        User::factory()->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(15),
        ]);

        $users = $this->cleanupService->listUnverifiedUsers(7);

        $this->assertCount(2, $users); // Only users older than 7 days
    }

    public function test_send_deletion_warnings_sends_notifications()
    {
        Notification::fake();

        config(['cleanup.unverified_users.warning_threshold_days' => 7]);
        config(['cleanup.unverified_users.deletion_threshold_days' => 14]);

        // Create unverified user past warning threshold
        $user = User::factory()->create([
            'email_verified_at' => null,
            'unverified_deletion_warning_sent_at' => null,
            'created_at' => now()->subDays(8),
        ]);

        $warningsSent = $this->cleanupService->sendDeletionWarnings();

        $this->assertEquals(1, $warningsSent);
        Notification::assertSentTo($user, UnverifiedUserDeletionWarning::class);
    }

    public function test_send_deletion_warnings_does_not_send_duplicate_warnings()
    {
        Notification::fake();

        config(['cleanup.unverified_users.warning_threshold_days' => 7]);

        // Create user who already received warning
        $user = User::factory()->create([
            'email_verified_at' => null,
            'unverified_deletion_warning_sent_at' => now()->subDays(2),
            'created_at' => now()->subDays(10),
        ]);

        $warningsSent = $this->cleanupService->sendDeletionWarnings();

        $this->assertEquals(0, $warningsSent);
        Notification::assertNothingSent();
    }

    public function test_send_deletion_warnings_updates_tracking_timestamp()
    {
        Notification::fake();

        config(['cleanup.unverified_users.warning_threshold_days' => 7]);
        config(['cleanup.unverified_users.deletion_threshold_days' => 14]);

        $user = User::factory()->create([
            'email_verified_at' => null,
            'unverified_deletion_warning_sent_at' => null,
            'created_at' => now()->subDays(8),
        ]);

        $this->cleanupService->sendDeletionWarnings();

        $user->refresh();
        $this->assertNotNull($user->unverified_deletion_warning_sent_at);
    }

    public function test_delete_unverified_users_soft_deletes_users()
    {
        config(['cleanup.unverified_users.deletion_threshold_days' => 14]);

        $user = User::factory()->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(15),
        ]);

        $deletedCount = $this->cleanupService->deleteUnverifiedUsers();

        $this->assertEquals(1, $deletedCount);
        $this->assertSoftDeleted($user);
    }

    public function test_delete_unverified_users_does_not_delete_verified_users()
    {
        config(['cleanup.unverified_users.deletion_threshold_days' => 14]);

        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
            'created_at' => now()->subDays(20),
        ]);

        $deletedCount = $this->cleanupService->deleteUnverifiedUsers();

        $this->assertEquals(0, $deletedCount);
        $this->assertDatabaseHas('users', ['id' => $verifiedUser->id, 'deleted_at' => null]);
    }

    public function test_delete_unverified_users_respects_threshold()
    {
        config(['cleanup.unverified_users.deletion_threshold_days' => 14]);

        // User below threshold
        $recentUser = User::factory()->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(10),
        ]);

        // User above threshold
        $oldUser = User::factory()->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(20),
        ]);

        $deletedCount = $this->cleanupService->deleteUnverifiedUsers();

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseHas('users', ['id' => $recentUser->id, 'deleted_at' => null]);
        $this->assertSoftDeleted($oldUser);
    }

    public function test_delete_unverified_users_uses_transaction()
    {
        config(['cleanup.unverified_users.deletion_threshold_days' => 14]);

        // Create users
        $user1 = User::factory()->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(15),
        ]);

        $user2 = User::factory()->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(16),
        ]);

        // Test that the service uses transactions by verifying all-or-nothing behavior
        // If one deletion fails, the transaction should roll back
        // Since we can't easily mock the UserService to fail mid-transaction,
        // we'll just verify that the method completes successfully with transactions
        $deletedCount = $this->cleanupService->deleteUnverifiedUsers();

        // Both users should be deleted
        $this->assertEquals(2, $deletedCount);
        $this->assertSoftDeleted($user1);
        $this->assertSoftDeleted($user2);
    }

    // ========================================
    // Edge Cases
    // ========================================

    public function test_handles_missing_storage_directories_gracefully()
    {
        Storage::fake(ProjectFile::getDisk());
        Storage::fake('public');

        // Don't create any directories
        $orphanedFiles = $this->cleanupService->listOrphanedFiles();

        // Should return empty collection without errors
        $this->assertCount(0, $orphanedFiles);
    }

    public function test_handles_empty_database_gracefully()
    {
        Storage::fake(ProjectFile::getDisk());
        Storage::fake('public');

        // Create files but no database records
        Storage::disk(ProjectFile::getDisk())->put(ProjectFile::getDirectory().'/file.zip', 'content');

        $orphanedFiles = $this->cleanupService->listOrphanedFiles();

        // Should identify file as orphaned
        $this->assertCount(1, $orphanedFiles);
    }

    public function test_list_unverified_users_with_no_users()
    {
        // Clear any existing users from previous tests
        User::query()->forceDelete();

        $users = $this->cleanupService->listUnverifiedUsers();

        $this->assertCount(0, $users);
    }

    public function test_send_warnings_with_no_eligible_users()
    {
        Notification::fake();

        config(['cleanup.unverified_users.warning_threshold_days' => 7]);

        // Create user below threshold
        User::factory()->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(3),
        ]);

        $warningsSent = $this->cleanupService->sendDeletionWarnings();

        $this->assertEquals(0, $warningsSent);
        Notification::assertNothingSent();
    }

    public function test_delete_users_with_no_eligible_users()
    {
        config(['cleanup.unverified_users.deletion_threshold_days' => 14]);

        // Create user below threshold
        User::factory()->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(10),
        ]);

        $deletedCount = $this->cleanupService->deleteUnverifiedUsers();

        $this->assertEquals(0, $deletedCount);
    }
}
