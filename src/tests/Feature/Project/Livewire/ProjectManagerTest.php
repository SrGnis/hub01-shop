<?php

namespace Tests\Feature\Project\Livewire;

use App\Livewire\ProjectManager;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectManagerTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->projectType = ProjectType::factory()->create();
        $this->user = User::factory()->create();
    }

    private function ownedProject(array $attributes = []): Project
    {
        $project = Project::factory()
            ->owner($this->user)
            ->create(array_merge([
                'project_type_id' => $this->projectType->id,
                'status' => 'active',
                'summary' => 'Valid summary text',
                'description' => 'Valid description text',
            ], $attributes));

        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);
        $project->tags()->syncWithoutDetaching([$tag->id]);

        return $project;
    }

    #[Test]
    public function test_component_renders_for_existing_project()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->assertOk()
            ->assertViewIs('livewire.project-manager')
            ->assertSet('currentSection', 'general')
            ->assertSet('name', $project->name)
            ->assertSet('slug', $project->slug);
    }

    #[Test]
    public function test_custom_slug_validation()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('slug', 'invalid slug with spaces')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    #[Test]
    public function test_slug_must_be_unique()
    {
        $targetProject = $this->ownedProject(['slug' => 'target-project']);
        $otherProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
            'slug' => 'existing-slug',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $targetProject])
            ->set('slug', $otherProject->slug)
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    #[Test]
    public function test_generate_slug_updates_slug_explicitly()
    {
        $project = $this->ownedProject(['slug' => 'original-slug']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('name', 'My Awesome Project')
            ->call('generateSlug')
            ->assertSet('slug', 'my-awesome-project');
    }

    #[Test]
    public function test_validation_requires_name()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function test_validation_requires_summary()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('summary', '')
            ->call('save')
            ->assertHasErrors(['summary']);
    }

    #[Test]
    public function test_validation_requires_description_for_approved_project()
    {
        $project = $this->ownedProject(['approval_status' => 'approved']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('description', '')
            ->call('save')
            ->assertHasErrors(['description']);
    }

    #[Test]
    public function test_validation_requires_at_least_one_tag_for_approved_project()
    {
        $project = $this->ownedProject(['approval_status' => 'approved']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('selectedTags', [])
            ->call('save')
            ->assertHasErrors(['selectedTags']);
    }

    #[Test]
    public function test_draft_project_can_save_without_description_or_tags()
    {
        $project = $this->ownedProject(['approval_status' => 'draft']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('description', '')
            ->set('selectedTags', [])
            ->call('save')
            ->assertHasNoErrors();
    }

    #[Test]
    public function test_send_to_review_enforces_strict_validation_for_description()
    {
        $project = $this->ownedProject(['approval_status' => 'draft', 'description' => '']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('description', '')
            ->call('sendToReview')
            ->assertHasErrors(['description']);
    }

    #[Test]
    public function test_send_to_review_enforces_strict_validation_for_tags()
    {
        $project = $this->ownedProject(['approval_status' => 'draft']);
        $project->tags()->detach();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('sendToReview')
            ->assertHasErrors(['selectedTags']);
    }

    #[Test]
    public function test_tags_must_belong_to_project_type()
    {
        $project = $this->ownedProject();
        $otherType = ProjectType::factory()->create();
        $invalidTag = ProjectTag::factory()->create();
        $invalidTag->projectTypes()->attach($otherType);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('selectedTags', [$invalidTag->id])
            ->call('save')
            ->assertHasErrors(['selectedTags']);
    }

    #[Test]
    public function test_update_project_with_only_subtags()
    {
        $project = $this->ownedProject();
        $parentTag = ProjectTag::factory()->create();
        $parentTag->projectTypes()->attach($this->projectType);
        $subTag = ProjectTag::factory()->create(['parent_id' => $parentTag->id]);
        $subTag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('selectedTags', [$subTag->id])
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertCount(2, $project->tags);
        $this->assertTrue($project->tags->contains('id', $subTag->id));
        $this->assertTrue($project->tags->contains('id', $parentTag->id));
    }

    #[Test]
    public function test_update_project_with_mixed_tags()
    {
        $project = $this->ownedProject();
        $parentTag1 = ProjectTag::factory()->create();
        $parentTag1->projectTypes()->attach($this->projectType);
        $parentTag2 = ProjectTag::factory()->create();
        $parentTag2->projectTypes()->attach($this->projectType);
        $subTag = ProjectTag::factory()->create(['parent_id' => $parentTag1->id]);
        $subTag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('selectedTags', [$parentTag2->id, $subTag->id])
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertCount(3, $project->tags);
        $this->assertTrue($project->tags->contains('id', $parentTag1->id));
        $this->assertTrue($project->tags->contains('id', $parentTag2->id));
        $this->assertTrue($project->tags->contains('id', $subTag->id));
    }

    #[Test]
    public function test_validate_subtag_parent_belongs_to_project_type()
    {
        $project = $this->ownedProject();
        $otherType = ProjectType::factory()->create();
        $parentTag = ProjectTag::factory()->create();
        $parentTag->projectTypes()->attach($otherType);
        $subTag = ProjectTag::factory()->create(['parent_id' => $parentTag->id]);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('selectedTags', [$subTag->id])
            ->call('save')
            ->assertHasErrors(['selectedTags']);
    }

    #[Test]
    public function test_update_project_replacing_tags_with_subtags()
    {
        $oldTag = ProjectTag::factory()->create();
        $oldTag->projectTypes()->attach($this->projectType);
        $project = $this->ownedProject();
        $project->tags()->attach($oldTag);

        $parentTag = ProjectTag::factory()->create();
        $parentTag->projectTypes()->attach($this->projectType);
        $subTag = ProjectTag::factory()->create(['parent_id' => $parentTag->id]);
        $subTag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('selectedTags', [$subTag->id])
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertCount(2, $project->tags);
        $this->assertTrue($project->tags->contains('id', $subTag->id));
        $this->assertTrue($project->tags->contains('id', $parentTag->id));
        $this->assertFalse($project->tags->contains('id', $oldTag->id));
    }

    #[Test]
    public function test_logo_upload()
    {
        $project = $this->ownedProject();
        $logo = UploadedFile::fake()->image('logo.png', 300, 300);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('logo', $logo)
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertNotNull($project->logo_path);
        Storage::disk('public')->assertExists($project->logo_path);
    }

    #[Test]
    public function test_logo_removal()
    {
        $project = $this->ownedProject(['logo_path' => 'project-logos/test.png']);
        Storage::disk('public')->put('project-logos/test.png', 'fake content');

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('removeLogo')
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertNull($project->logo_path);
    }

    #[Test]
    public function test_update_existing_project_dispatches_success_event()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('name', 'Updated Name')
            ->set('summary', 'Updated summary')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('project-manager-save-succeeded');

        $this->assertDatabaseHas('project', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'summary' => 'Updated summary',
        ]);
    }

    #[Test]
    public function test_cannot_edit_deactivated_project()
    {
        $project = $this->ownedProject(['deactivated_at' => now()]);

        $this->actingAs($this->user)
            ->get(route('project.manage', ['projectType' => $this->projectType, 'project' => $project]))
            ->assertRedirect(route('project.show', ['projectType' => $this->projectType, 'project' => $project]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function test_non_owner_cannot_edit_project()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);

        $this->actingAs($otherUser)
            ->get(route('project.manage', ['projectType' => $this->projectType, 'project' => $project]))
            ->assertRedirect(route('project.show', ['projectType' => $this->projectType, 'project' => $project]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function test_guest_is_redirected_from_manage_route()
    {
        $project = $this->ownedProject();

        $this->actingAsGuest()
            ->get(route('project.manage', ['projectType' => $this->projectType, 'project' => $project]))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function test_mount_falls_back_to_general_for_invalid_section()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, [
                'projectType' => $this->projectType,
                'project' => $project,
                'section' => 'invalid-section',
            ])
            ->assertSet('currentSection', 'general');
    }

    #[Test]
    public function test_mount_keeps_valid_requested_section()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, [
                'projectType' => $this->projectType,
                'project' => $project,
                'section' => 'links',
            ])
            ->assertSet('currentSection', 'links');
    }

    #[Test]
    public function test_mount_accepts_analytics_section(): void
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, [
                'projectType' => $this->projectType,
                'project' => $project,
                'section' => 'analytics',
            ])
            ->assertSet('currentSection', 'analytics');
    }

    #[Test]
    public function test_manage_analytics_route_is_accessible_for_authorized_user(): void
    {
        $project = $this->ownedProject();

        $this->actingAs($this->user)
            ->get(route('project.manage', ['projectType' => $this->projectType, 'project' => $project, 'section' => 'analytics']))
            ->assertOk()
            ->assertSee('Trends');
    }

    #[Test]
    public function test_manage_analytics_route_is_blocked_for_draft_project(): void
    {
        $project = $this->ownedProject(['approval_status' => 'draft']);

        $this->actingAs($this->user)
            ->get(route('project.manage', ['projectType' => $this->projectType, 'project' => $project, 'section' => 'analytics']))
            ->assertRedirect(route('project.manage', ['projectType' => $this->projectType, 'project' => $project, 'section' => 'general']))
            ->assertSessionHas('error', 'Analytics are only available for approved projects.');
    }

    #[Test]
    public function test_manage_analytics_nav_is_hidden_for_draft_project(): void
    {
        $project = $this->ownedProject(['approval_status' => 'draft']);

        $this->actingAs($this->user)
            ->get(route('project.manage', ['projectType' => $this->projectType, 'project' => $project, 'section' => 'general']))
            ->assertOk()
            ->assertDontSee('Analytics');
    }

    #[Test]
    public function test_manage_analytics_route_is_blocked_for_unauthorized_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);

        $this->actingAs($otherUser)
            ->get(route('project.manage', ['projectType' => $this->projectType, 'project' => $project, 'section' => 'analytics']))
            ->assertRedirect(route('project.show', ['projectType' => $this->projectType, 'project' => $project]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function test_submit_draft_for_review()
    {
        $project = $this->ownedProject(['approval_status' => 'draft']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('sendToReview')
            ->assertHasNoErrors();

        $expectedStatus = config('projects.auto_approve', false) ? 'approved' : 'pending';
        $this->assertDatabaseHas('project', ['id' => $project->id, 'approval_status' => $expectedStatus]);
    }

    #[Test]
    public function test_submit_rejected_project_for_review()
    {
        $project = $this->ownedProject(['approval_status' => 'rejected']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('sendToReview')
            ->assertHasNoErrors();

        $expectedStatus = config('projects.auto_approve', false) ? 'approved' : 'pending';
        $this->assertDatabaseHas('project', ['id' => $project->id, 'approval_status' => $expectedStatus]);
    }

    #[Test]
    public function test_cannot_submit_approved_project_for_review()
    {
        $project = $this->ownedProject(['approval_status' => 'approved']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('sendToReview');

        $this->assertDatabaseHas('project', ['id' => $project->id, 'approval_status' => 'approved']);
    }

    #[Test]
    public function test_add_member_to_project()
    {
        $project = $this->ownedProject();
        $newMember = User::factory()->create();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('newMemberName', $newMember->name)
            ->set('newMemberRole', 'contributor')
            ->call('addMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('membership', [
            'project_id' => $project->id,
            'user_id' => $newMember->id,
            'role' => 'contributor',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function test_cannot_add_nonexistent_user_as_member()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('newMemberName', 'nonexistent-user')
            ->set('newMemberRole', 'contributor')
            ->call('addMember')
            ->assertHasErrors(['newMemberName']);
    }

    #[Test]
    public function test_remove_member_from_project()
    {
        $project = $this->ownedProject();
        $member = User::factory()->create();

        $membership = new Membership(['role' => 'contributor', 'primary' => false, 'status' => 'active']);
        $membership->user()->associate($member);
        $membership->project()->associate($project);
        $membership->save();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('removeMember', $membership->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('membership', ['id' => $membership->id]);
    }

    #[Test]
    public function test_cannot_remove_yourself_as_primary_owner()
    {
        $project = $this->ownedProject();
        $membership = $project->memberships()->where('user_id', $this->user->id)->first();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('removeMember', $membership->id);

        $this->assertDatabaseHas('membership', ['id' => $membership->id]);
    }

    #[Test]
    public function test_set_primary_member()
    {
        $project = $this->ownedProject();
        $newOwner = User::factory()->create();

        $membership = new Membership(['role' => 'owner', 'primary' => false, 'status' => 'active']);
        $membership->user()->associate($newOwner);
        $membership->project()->associate($project);
        $membership->save();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('setPrimaryMember', $membership->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('membership', ['id' => $membership->id, 'primary' => true]);
        $oldPrimaryMembership = $project->memberships()->where('user_id', $this->user->id)->first();
        $this->assertFalse($oldPrimaryMembership->fresh()->primary);
    }

    #[Test]
    public function test_delete_project()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('deleteConfirmation', $project->name)
            ->call('deleteProject')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('project', ['id' => $project->id]);
    }

    #[Test]
    public function test_delete_project_requires_correct_confirmation()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('deleteConfirmation', 'wrong name')
            ->call('deleteProject')
            ->assertHasErrors(['deleteConfirmation']);

        $this->assertDatabaseHas('project', ['id' => $project->id, 'deleted_at' => null]);
    }

    #[Test]
    public function test_non_owner_cannot_delete_project()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->owner($owner)->member($otherUser)->create(['project_type_id' => $this->projectType->id]);

        Livewire::actingAs($otherUser)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('deleteConfirmation', $project->name)
            ->call('deleteProject');

        $this->assertDatabaseHas('project', ['id' => $project->id, 'deleted_at' => null]);
    }

    #[Test]
    public function test_url_validation()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('website', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['website']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('issues', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['issues']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('source', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['source']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('externalCredits', [[
                'name' => 'Jane Doe',
                'role' => 'Composer',
                'url' => 'not-a-url',
            ]])
            ->call('save')
            ->assertHasErrors(['externalCredits.0.url']);
    }

    #[Test]
    public function test_validation_requires_status()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('status', '')
            ->call('save')
            ->assertHasErrors(['status']);
    }

    #[Test]
    public function test_validation_rejects_invalid_status_value()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('status', 'archived')
            ->call('save')
            ->assertHasErrors(['status']);
    }

    #[Test]
    public function test_updated_slug_validates_immediately()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('slug', 'invalid slug with spaces')
            ->assertHasErrors(['slug']);
    }

    #[Test]
    public function test_external_credit_name_is_required()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('externalCredits', [[
                'name' => '',
                'role' => 'Composer',
                'url' => 'https://example.com/jane',
            ]])
            ->call('save')
            ->assertHasErrors(['externalCredits.0.name']);
    }

    #[Test]
    public function test_external_credit_role_is_required()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('externalCredits', [[
                'name' => 'Jane Doe',
                'role' => '',
                'url' => 'https://example.com/jane',
            ]])
            ->call('save')
            ->assertHasErrors(['externalCredits.0.role']);
    }

    #[Test]
    public function test_update_project_with_external_credits()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('externalCredits', [
                ['name' => 'Jane Doe', 'role' => 'Composer', 'url' => 'https://example.com/jane'],
                ['name' => 'John Roe', 'role' => 'Concept Artist', 'url' => null],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_external_credit', [
            'project_id' => $project->id,
            'name' => 'Jane Doe',
            'role' => 'Composer',
            'url' => 'https://example.com/jane',
        ]);

        $this->assertDatabaseHas('project_external_credit', [
            'project_id' => $project->id,
            'name' => 'John Roe',
            'role' => 'Concept Artist',
            'url' => null,
        ]);
    }

    #[Test]
    public function test_can_add_external_credit_row_in_manager()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->assertSet('externalCredits', [])
            ->call('addExternalCredit')
            ->assertSet('externalCredits.0.name', '')
            ->assertSet('externalCredits.0.role', '')
            ->assertSet('externalCredits.0.url', '');
    }

    #[Test]
    public function test_can_remove_external_credit_row_and_reindex_data()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('externalCredits', [
                ['name' => 'Jane Doe', 'role' => 'Composer', 'url' => 'https://example.com/jane'],
                ['name' => 'John Roe', 'role' => 'Artist', 'url' => 'https://example.com/john'],
            ])
            ->call('removeExternalCredit', 0)
            ->assertSet('externalCredits.0.name', 'John Roe')
            ->assertSet('externalCredits.0.role', 'Artist')
            ->assertSet('externalCredits.0.url', 'https://example.com/john');
    }

    #[Test]
    public function test_manager_loads_existing_external_credits()
    {
        $project = $this->ownedProject();
        $project->externalCredits()->create(['name' => 'Jane Doe', 'role' => 'Composer', 'url' => 'https://example.com/jane']);
        $project->externalCredits()->create(['name' => 'John Roe', 'role' => 'Concept Artist', 'url' => null]);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->assertSet('externalCredits.0.name', 'Jane Doe')
            ->assertSet('externalCredits.0.role', 'Composer')
            ->assertSet('externalCredits.0.url', 'https://example.com/jane')
            ->assertSet('externalCredits.1.name', 'John Roe')
            ->assertSet('externalCredits.1.role', 'Concept Artist')
            ->assertSet('externalCredits.1.url', null);
    }

    #[Test]
    public function test_slug_does_not_auto_generate_when_editing()
    {
        $project = $this->ownedProject(['slug' => 'original-slug']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('name', 'Completely New Name')
            ->assertSet('slug', 'original-slug');
    }

    #[Test]
    public function test_set_section_updates_only_for_allowed_sections()
    {
        $project = $this->ownedProject();

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->assertSet('currentSection', 'general')
            ->call('setSection', 'members')
            ->assertSet('currentSection', 'members')
            ->call('setSection', 'analytics')
            ->assertSet('currentSection', 'analytics')
            ->call('setSection', 'not-allowed')
            ->assertSet('currentSection', 'analytics');
    }

    #[Test]
    public function test_set_section_does_not_switch_to_analytics_for_draft_project()
    {
        $project = $this->ownedProject(['approval_status' => 'draft']);

        Livewire::actingAs($this->user)
            ->test(ProjectManager::class, ['projectType' => $this->projectType, 'project' => $project])
            ->assertSet('currentSection', 'general')
            ->call('setSection', 'analytics')
            ->assertSet('currentSection', 'general');
    }
}
