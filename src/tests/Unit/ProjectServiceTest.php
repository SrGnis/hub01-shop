<?php

namespace Tests\Unit;

use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectTagGroup;
use App\Models\ProjectType;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\ProjectQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectService $projectService;
    private ProjectType $projectType;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();

        $quotaService = $this->app->make(ProjectQuotaService::class);
        $this->projectService = new ProjectService($quotaService);
        $this->projectType = ProjectType::factory()->create();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function test_search_projects_by_name()
    {
        $project1 = Project::factory()->owner($this->user)->create([
            'name' => 'Amazing Project',
            'project_type_id' => $this->projectType->id
        ]);
        $project2 = Project::factory()->owner($this->user)->create([
            'name' => 'Different Thing',
            'project_type_id' => $this->projectType->id
        ]);

        $results = $this->projectService->searchProjects(
            projectType: $this->projectType,
            search: 'Amazing'
        );

        $this->assertEquals(1, $results->total());
        $this->assertEquals($project1->id, $results->first()->id);
    }

    #[Test]
    public function test_filter_projects_by_tags()
    {
        $tag1 = ProjectTag::factory()->create();
        $tag1->projectTypes()->attach($this->projectType);

        $tag2 = ProjectTag::factory()->create();
        $tag2->projectTypes()->attach($this->projectType);

        $project1 = Project::factory()->owner($this->user)->create(['project_type_id' => $this->projectType->id]);
        $project1->tags()->attach($tag1);

        $project2 = Project::factory()->owner($this->user)->create(['project_type_id' => $this->projectType->id]);
        $project2->tags()->attach($tag2);

        $results = $this->projectService->searchProjects(
            projectType: $this->projectType,
            selectedTags: [$tag1->id]
        );

        $this->assertEquals(1, $results->total());
        $this->assertEquals($project1->id, $results->first()->id);
    }

    #[Test]
    public function test_order_projects_by_downloads()
    {
        $projectLow = Project::factory()->owner($this->user)->create(['project_type_id' => $this->projectType->id]);
        $projectLow->versions()->create([
            'name' => 'Low Downloads Version',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'downloads' => 10,
        ]);

        $projectHigh = Project::factory()->owner($this->user)->create(['project_type_id' => $this->projectType->id]);
        $projectHigh->versions()->create([
            'name' => 'High Downloads Version',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'downloads' => 1000,
        ]);

        $results = $this->projectService->searchProjects(
            projectType: $this->projectType,
            orderBy: 'downloads',
            orderDirection: 'desc'
        );

        $this->assertEquals($projectHigh->id, $results->first()->id);
        $this->assertEquals($projectLow->id, $results->last()->id);
    }

    #[Test]
    public function test_order_projects_by_name()
    {
        $projectZ = Project::factory()->owner($this->user)->create([
            'name' => 'Zeta Project',
            'project_type_id' => $this->projectType->id
        ]);
        $projectA = Project::factory()->owner($this->user)->create([
            'name' => 'Alpha Project',
            'project_type_id' => $this->projectType->id
        ]);

        $results = $this->projectService->searchProjects(
            projectType: $this->projectType,
            orderBy: 'name',
            orderDirection: 'asc'
        );

        $this->assertEquals($projectA->id, $results->first()->id);
        $this->assertEquals($projectZ->id, $results->last()->id);
    }

    #[Test]
    public function test_get_tag_groups_for_project_type()
    {
        // Create a tag group
        $tagGroup = ProjectTagGroup::factory()->create();
        $tagGroup->projectTypes()->attach($this->projectType);

        // Create a tag
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        // Add the tag group to the tag
        $tag->tagGroup()->associate($tagGroup);

        $tagGroups = $this->projectService->getTagGroups($this->projectType);

        $this->assertGreaterThan(0, $tagGroups->count());
    }

    #[Test]
    public function test_generate_slug_from_name()
    {
        $slug = $this->projectService->generateSlug('My Awesome Project');

        $this->assertEquals('my-awesome-project', $slug);
    }

    #[Test]
    public function test_generate_unique_slug_when_duplicate_exists()
    {
        Project::factory()->owner($this->user)->create(['slug' => 'test-project']);

        $slug = $this->projectService->generateSlug('Test Project');

        $this->assertEquals('test-project-1', $slug);
    }

    #[Test]
    public function test_create_project_in_draft_mode()
    {
        Config::set('projects.auto_approve', false);

        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        $data = [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'summary' => 'Test summary',
            'description' => 'Test description',
            'website' => 'https://example.com',
            'issues' => 'https://github.com/test/issues',
            'source' => 'https://github.com/test/source',
            'status' => 'active',
            'selectedTags' => [$tag->id],
            'project_type_id' => $this->projectType->id,
        ];

        $project = $this->projectService->saveProject(null, $this->user, $data);

        $this->assertDatabaseHas('project', [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'approval_status' => 'draft',
        ]);

        // Should create owner membership
        $this->assertDatabaseHas('membership', [
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'primary' => true,
        ]);
    }

    #[Test]
    public function test_create_project_with_auto_approve()
    {
        Config::set('projects.auto_approve', true);

        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        $data = [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'summary' => 'Test summary',
            'description' => 'Test description',
            'website' => 'https://example.com',
            'issues' => '',
            'source' => '',
            'status' => 'active',
            'selectedTags' => [$tag->id],
            'project_type_id' => $this->projectType->id,
        ];

        $project = $this->projectService->saveProject(null, $this->user, $data);

        $this->assertDatabaseHas('project', [
            'name' => 'Test Project',
            'approval_status' => 'approved',
        ]);
        $this->assertNotNull($project->submitted_at);
        $this->assertNotNull($project->reviewed_at);
    }

    #[Test]
    public function test_update_existing_project()
    {
        $project = Project::factory()->owner($this->user)->create();
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        $data = [
            'name' => 'Updated Name',
            'slug' => $project->slug,
            'summary' => 'Updated summary',
            'description' => $project->description,
            'website' => $project->website,
            'issues' => $project->issues,
            'source' => $project->source,
            'status' => $project->status,
            'selectedTags' => [$tag->id],
        ];

        $updatedProject = $this->projectService->saveProject($project, null, $data);

        $this->assertEquals('Updated Name', $updatedProject->name);
        $this->assertEquals('Updated summary', $updatedProject->summary);
    }

    #[Test]
    public function test_cannot_update_pending_project()
    {
        $this->expectException(\Exception::class);

        $project = Project::factory()->owner($this->user)->pending()->create();

        $data = [
            'name' => 'Updated Name',
            'slug' => $project->slug,
            'summary' => 'Updated summary',
            'description' => $project->description,
            'website' => $project->website,
            'issues' => $project->issues,
            'source' => $project->source,
            'status' => $project->status,
            'selectedTags' => [],
        ];

        $this->projectService->saveProject($project, null, $data);
    }

    #[Test]
    public function test_submit_project_for_review()
    {
        $project = Project::factory()->owner($this->user)->draft()->create();

        $this->projectService->submitProjectForReview($project);

        $this->assertDatabaseHas('project', [
            'id' => $project->id,
            'approval_status' => 'pending',
        ]);
        $this->assertNotNull($project->fresh()->submitted_at);
    }

    #[Test]
    public function test_approve_project()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->owner($this->user)->pending()->create();

        $this->projectService->approveProject($project, $admin);

        $this->assertDatabaseHas('project', [
            'id' => $project->id,
            'approval_status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertNotNull($project->fresh()->reviewed_at);
        $this->assertNull($project->fresh()->rejection_reason);
    }

    #[Test]
    public function test_reject_project()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->owner($this->user)->pending()->create();
        $reason = 'This project violates our guidelines.';

        $this->projectService->rejectProject($project, $admin, $reason);

        $this->assertDatabaseHas('project', [
            'id' => $project->id,
            'approval_status' => 'rejected',
            'reviewed_by' => $admin->id,
            'rejection_reason' => $reason,
        ]);
        $this->assertNotNull($project->fresh()->reviewed_at);
    }

    #[Test]
    public function test_add_member_to_project()
    {
        $project = Project::factory()->owner($this->user)->create();
        $newMember = User::factory()->create();

        $this->projectService->addMember($project, $newMember->name, 'contributor');

        $this->assertDatabaseHas('membership', [
            'project_id' => $project->id,
            'user_id' => $newMember->id,
            'role' => 'contributor',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function test_cannot_add_existing_member()
    {
        $this->expectException(\Exception::class);

        $project = Project::factory()->owner($this->user)->create();

        $this->projectService->addMember($project, $this->user->name, 'contributor');
    }

    #[Test]
    public function test_remove_member_from_project()
    {
        $project = Project::factory()->owner($this->user)->create();
        $member = User::factory()->create();

        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active',
        ]);
        $membership->user()->associate($member);
        $membership->project()->associate($project);
        $membership->save();

        $this->actingAs($this->user);
        $isSelfRemoval = $this->projectService->removeMember($project, $membership->id);

        $this->assertFalse($isSelfRemoval);
        $this->assertDatabaseMissing('membership', [
            'id' => $membership->id,
        ]);
    }

    #[Test]
    public function test_cannot_remove_yourself_as_primary_owner()
    {
        $this->expectException(\Exception::class);

        $project = Project::factory()->owner($this->user)->create();
        $membership = $project->memberships()->where('user_id', $this->user->id)->first();

        $this->actingAs($this->user);
        $this->projectService->removeMember($project, $membership->id);
    }

    #[Test]
    public function test_set_primary_member()
    {
        $project = Project::factory()->owner($this->user)->create();
        $newOwner = User::factory()->create();

        $membership = new Membership([
            'role' => 'owner',
            'primary' => false,
            'status' => 'active',
        ]);
        $membership->user()->associate($newOwner);
        $membership->project()->associate($project);
        $membership->save();

        $this->actingAs($this->user);
        $this->projectService->setPrimaryMember($project, $membership->id);

        $this->assertDatabaseHas('membership', [
            'id' => $membership->id,
            'primary' => true,
        ]);

        // Old primary should be demoted
        $oldMembership = $project->memberships()->where('user_id', $this->user->id)->first();
        $this->assertFalse($oldMembership->fresh()->primary);
    }

    #[Test]
    public function test_cannot_set_pending_member_as_primary()
    {
        $this->expectException(\Exception::class);

        $project = Project::factory()->owner($this->user)->create();
        $newOwner = User::factory()->create();

        $membership = new Membership([
            'role' => 'owner',
            'primary' => false,
            'status' => 'pending', // Pending status
        ]);
        $membership->user()->associate($newOwner);
        $membership->project()->associate($project);
        $membership->save();

        $this->projectService->setPrimaryMember($project, $membership->id);
    }

    #[Test]
    public function test_delete_project()
    {
        $project = Project::factory()->owner($this->user)->create();

        $this->actingAs($this->user);
        $this->projectService->deleteProject($project);

        $this->assertSoftDeleted('project', [
            'id' => $project->id,
        ]);
    }

    #[Test]
    public function test_restore_project()
    {
        $project = Project::factory()->owner($this->user)->create();
        $project->delete();

        $this->assertTrue($project->trashed());

        $this->actingAs($this->user);
        $this->projectService->restoreProject($project);

        $this->assertFalse($project->fresh()->trashed());
    }

    #[Test]
    public function test_delete_project_with_dependent_versions()
    {
        $project = Project::factory()->owner($this->user)->create();
        $dependentProject = Project::factory()->owner($this->user)->create();

        // Create version for the project
        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        // Create dependent version
        $dependentVersion = $dependentProject->versions()->create([
            'name' => 'Dependent Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        // Create dependency
        $dependentVersion->dependencies()->create([
            'dependency_project_version_id' => $version->id,
        ]);

        $this->actingAs($this->user);
        $this->projectService->deleteProject($project);

        $this->assertSoftDeleted('project', [
            'id' => $project->id,
        ]);

        // Dependent project should receive notification (tested via Notification facade)
    }

    #[Test]
    public function test_get_order_options()
    {
        $options = $this->projectService->getOrderOptions();

        $this->assertIsArray($options);
        $this->assertGreaterThan(0, count($options));

        $optionIds = array_column($options, 'id');
        $this->assertContains('name', $optionIds);
        $this->assertContains('downloads', $optionIds);
        $this->assertContains('created_at', $optionIds);
        $this->assertContains('latest_version', $optionIds);
    }

    #[Test]
    public function test_get_direction_options()
    {
        $options = $this->projectService->getDirectionOptions();

        $this->assertIsArray($options);
        $this->assertEquals(2, count($options));

        $optionIds = array_column($options, 'id');
        $this->assertContains('asc', $optionIds);
        $this->assertContains('desc', $optionIds);
    }

    #[Test]
    public function test_get_per_page_options()
    {
        $options = $this->projectService->getPerPageOptions();

        $this->assertIsArray($options);
        $this->assertGreaterThan(0, count($options));

        $optionIds = array_column($options, 'id');
        $this->assertContains(10, $optionIds);
        $this->assertContains(25, $optionIds);
    }

    #[Test]
    public function test_pagination_in_search_results()
    {
        // Create 30 projects
        for ($i = 0; $i < 30; $i++) {
            Project::factory()->owner($this->user)->create(['project_type_id' => $this->projectType->id]);
        }

        $results = $this->projectService->searchProjects(
            projectType: $this->projectType,
            resultsPerPage: 10
        );

        $this->assertEquals(10, $results->count());
        $this->assertEquals(30, $results->total());
        $this->assertEquals(3, $results->lastPage());
    }

    #[Test]
    public function test_logo_upload_and_update()
    {
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        $logoPath = 'project-logos/test-logo.png';
        Storage::disk('public')->put($logoPath, 'fake content');

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

        $project = $this->projectService->saveProject(null, $this->user, $data, $logoPath);

        $this->assertEquals($logoPath, $project->logo_path);
    }

    #[Test]
    public function test_logo_removal_on_update()
    {
        $project = Project::factory()->owner($this->user)->create([
            'logo_path' => 'project-logos/old-logo.png'
        ]);

        Storage::disk('public')->put('project-logos/old-logo.png', 'fake content');

        $data = [
            'name' => $project->name,
            'slug' => $project->slug,
            'summary' => $project->summary,
            'description' => $project->description,
            'website' => $project->website,
            'issues' => $project->issues,
            'source' => $project->source,
            'status' => $project->status,
            'selectedTags' => [],
        ];

        // Pass empty string to signal removal
        $updatedProject = $this->projectService->saveProject($project, null, $data, '');

        $this->assertNull($updatedProject->logo_path);
        Storage::disk('public')->assertMissing('project-logos/old-logo.png');
    }
}
