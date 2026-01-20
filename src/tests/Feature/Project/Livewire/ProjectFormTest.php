<?php

namespace Tests\Feature\Project\Livewire;

use App\Livewire\ProjectForm;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectFormTest extends TestCase
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

    #[Test]
    public function test_component_renders_for_new_project()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->assertOk()
            ->assertViewIs('livewire.project-form')
            ->assertSet('isEditing', false);
    }

    #[Test]
    public function test_component_renders_for_existing_project()
    {
        $project = Project::factory()->owner($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->assertOk()
            ->assertSet('isEditing', true)
            ->assertSet('name', $project->name)
            ->assertSet('slug', $project->slug);
    }

    #[Test]
    public function test_guest_is_redirected_on_create()
    {
        $this->actingAsGuest()
            ->get(route('project.create', ['projectType' => $this->projectType]))
            ->assertRedirect(route('verification.notice'));
    }

    #[Test]
    public function test_unverified_user_cannot_create_project()
    {
        $unverifiedUser = User::factory()->unverified()->create();

        $this->actingAs($unverifiedUser)
            ->get(route('project.create', ['projectType' => $this->projectType]))
            ->assertRedirect(route('verification.notice'));
    }

    #[Test]
    public function test_create_project_in_draft_mode()
    {
        Config::set('projects.auto_approve', false);
        
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$tag->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('project', [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'approval_status' => 'draft',
        ]);
    }

    #[Test]
    public function test_create_project_with_auto_approve()
    {
        Config::set('projects.auto_approve', true);
        
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$tag->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project', [
            'name' => 'Test Project',
            'slug' => 'test-project',
            'approval_status' => 'approved',
        ]);
    }

    #[Test]
    public function test_slug_is_generated_from_name()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'My Awesome Project')
            ->assertSet('slug', 'my-awesome-project');
    }

    #[Test]
    public function test_custom_slug_validation()
    {
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Test Project')
            ->set('slug', 'invalid slug with spaces')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$tag->id])
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    #[Test]
    public function test_slug_must_be_unique()
    {
        $existingProject = Project::factory()->owner($this->user)->create(['slug' => 'existing-slug']);
        
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'New Project')
            ->set('slug', 'existing-slug')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$tag->id])
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    #[Test]
    public function test_validation_requires_name()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function test_validation_requires_summary()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('summary', '')
            ->call('save')
            ->assertHasErrors(['summary']);
    }

    #[Test]
    public function test_validation_requires_description()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('description', '')
            ->call('save')
            ->assertHasErrors(['description']);
    }

    #[Test]
    public function test_validation_requires_at_least_one_tag()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [])
            ->call('save')
            ->assertHasErrors(['selectedTags']);
    }

    #[Test]
    public function test_tags_must_belong_to_project_type()
    {
        $otherProjectType = ProjectType::factory()->create();
        $invalidTag = ProjectTag::factory()->create();
        $invalidTag->projectTypes()->attach($otherProjectType);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$invalidTag->id])
            ->call('save')
            ->assertHasErrors(['selectedTags']);
    }

    #[Test]
    public function test_create_project_with_only_subtags()
    {
        $parentTag = ProjectTag::factory()->create();
        $parentTag->projectTypes()->attach($this->projectType);

        $subTag = ProjectTag::factory()->create(['parent_id' => $parentTag->id]);
        $subTag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$subTag->id]) // Only select subtag
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('slug', 'test-project')->first();

        // Should have both the subtag and its parent tag
        $this->assertCount(2, $project->tags);
        $this->assertTrue($project->tags->contains('id', $subTag->id));
        $this->assertTrue($project->tags->contains('id', $parentTag->id));
    }

    #[Test]
    public function test_create_project_with_mixed_tags()
    {
        $parentTag1 = ProjectTag::factory()->create();
        $parentTag1->projectTypes()->attach($this->projectType);

        $parentTag2 = ProjectTag::factory()->create();
        $parentTag2->projectTypes()->attach($this->projectType);

        $subTag = ProjectTag::factory()->create(['parent_id' => $parentTag1->id]);
        $subTag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$parentTag2->id, $subTag->id]) // Mix: one parent, one subtag
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('slug', 'test-project')->first();

        // Should have 3 tags: parentTag2, subTag, and parentTag1 (auto-added)
        $this->assertCount(3, $project->tags);
        $this->assertTrue($project->tags->contains('id', $parentTag1->id));
        $this->assertTrue($project->tags->contains('id', $parentTag2->id));
        $this->assertTrue($project->tags->contains('id', $subTag->id));
    }

    #[Test]
    public function test_validate_subtag_parent_belongs_to_project_type()
    {
        $otherProjectType = ProjectType::factory()->create();

        // Parent tag belongs to OTHER project type
        $parentTag = ProjectTag::factory()->create();
        $parentTag->projectTypes()->attach($otherProjectType);

        // Subtag (child of parent that doesn't belong to our project type)
        $subTag = ProjectTag::factory()->create(['parent_id' => $parentTag->id]);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$subTag->id])
            ->call('save')
            ->assertHasErrors(['selectedTags']); // Should fail because parent doesn't belong to project type
    }

    #[Test]
    public function test_invalid_subtag_with_wrong_parent()
    {
        $correctParentTag = ProjectTag::factory()->create();
        $correctParentTag->projectTypes()->attach($this->projectType);

        $wrongParentTag = ProjectTag::factory()->create(); // Not associated with project type

        $subTag = ProjectTag::factory()->create(['parent_id' => $wrongParentTag->id]);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$subTag->id])
            ->call('save')
            ->assertHasErrors(['selectedTags']);
    }

    #[Test]
    public function test_update_project_replacing_tags_with_subtags()
    {
        $oldTag = ProjectTag::factory()->create();
        $oldTag->projectTypes()->attach($this->projectType);

        $project = Project::factory()->owner($this->user)->create(['project_type_id' => $this->projectType->id]);
        $project->tags()->attach($oldTag);

        $parentTag = ProjectTag::factory()->create();
        $parentTag->projectTypes()->attach($this->projectType);

        $subTag = ProjectTag::factory()->create(['parent_id' => $parentTag->id]);
        $subTag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$subTag->id]) // Replace old tag with subtag
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();

        // Should have subtag and its parent, but not the old tag
        $this->assertCount(2, $project->tags);
        $this->assertTrue($project->tags->contains('id', $subTag->id));
        $this->assertTrue($project->tags->contains('id', $parentTag->id));
        $this->assertFalse($project->tags->contains('id', $oldTag->id));
    }


    #[Test]
    public function test_logo_upload()
    {
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);
        $logo = UploadedFile::fake()->image('logo.png', 300, 300);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('name', 'Test Project')
            ->set('slug', 'test-project')
            ->set('summary', 'A test summary')
            ->set('description', 'A test description')
            ->set('selectedTags', [$tag->id])
            ->set('logo', $logo)
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('slug', 'test-project')->first();
        $this->assertNotNull($project->logo_path);
        Storage::disk('public')->assertExists($project->logo_path);
    }

    #[Test]
    public function test_logo_removal()
    {
        $project = Project::factory()->owner($this->user)->create([
            'logo_path' => 'project-logos/test.png'
        ]);
        
        Storage::disk('public')->put('project-logos/test.png', 'fake content');

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('removeLogo')
            ->assertSet('shouldRemoveLogo', true)
            ->assertSet('logo', null);
    }

    #[Test]
    public function test_update_existing_project()
    {
        $project = Project::factory()->owner($this->user)->create();
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('name', 'Updated Name')
            ->set('summary', 'Updated summary')
            ->set('selectedTags', [$tag->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'summary' => 'Updated summary',
        ]);
    }

    #[Test]
    public function test_cannot_edit_deactivated_project()
    {
        $project = Project::factory()->owner($this->user)->create([
            'deactivated_at' => now()
        ]);

        $this->actingAs($this->user)
            ->get(route('project.edit', ['projectType' => $this->projectType, 'project' => $project]))
            ->assertRedirect(route('project-search', ['projectType' => $this->projectType]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function test_non_owner_cannot_edit_project()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->owner($owner)->create();

        $this->actingAs($otherUser)
            ->get(route('project.edit', ['projectType' => $this->projectType, 'project' => $project]))
            ->assertRedirect(route('project.show', ['projectType' => $this->projectType, 'project' => $project]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function test_submit_draft_for_review()
    {
        $project = Project::factory()->owner($this->user)->draft()->create();

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('sendToReview')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('project', [
            'id' => $project->id,
            'approval_status' => 'pending',
        ]);
    }

    #[Test]
    public function test_submit_rejected_project_for_review()
    {
        $project = Project::factory()->owner($this->user)->rejected()->create();

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('sendToReview')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project', [
            'id' => $project->id,
            'approval_status' => 'pending',
        ]);
    }

    #[Test]
    public function test_cannot_submit_approved_project_for_review()
    {
        $project = Project::factory()->owner($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('sendToReview');

        // Should remain approved
        $this->assertDatabaseHas('project', [
            'id' => $project->id,
            'approval_status' => 'approved',
        ]);
    }

    #[Test]
    public function test_add_member_to_project()
    {
        $project = Project::factory()->owner($this->user)->create();
        $newMember = User::factory()->create();

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
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
        $project = Project::factory()->owner($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('newMemberName', 'nonexistent-user')
            ->set('newMemberRole', 'contributor')
            ->call('addMember')
            ->assertHasErrors(['newMemberName']);
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

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('removeMember', $membership->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('membership', [
            'id' => $membership->id,
        ]);
    }

    #[Test]
    public function test_cannot_remove_yourself_as_primary_owner()
    {
        $project = Project::factory()->owner($this->user)->create();
        $membership = $project->memberships()->where('user_id', $this->user->id)->first();

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('removeMember', $membership->id);

        // Membership should still exist
        $this->assertDatabaseHas('membership', [
            'id' => $membership->id,
        ]);
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

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->call('setPrimaryMember', $membership->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('membership', [
            'id' => $membership->id,
            'primary' => true,
        ]);

        // Old primary should be demoted
        $oldPrimaryMembership = $project->memberships()->where('user_id', $this->user->id)->first();
        $this->assertFalse($oldPrimaryMembership->fresh()->primary);
    }

    #[Test]
    public function test_delete_project()
    {
        $project = Project::factory()->owner($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('deleteConfirmation', $project->name)
            ->call('deleteProject')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSoftDeleted('project', [
            'id' => $project->id,
        ]);
    }

    #[Test]
    public function test_delete_project_requires_correct_confirmation()
    {
        $project = Project::factory()->owner($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('deleteConfirmation', 'wrong name')
            ->call('deleteProject')
            ->assertHasErrors(['deleteConfirmation']);

        $this->assertDatabaseHas('project', [
            'id' => $project->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function test_non_owner_cannot_delete_project()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->owner($owner)->member($otherUser)->create();

        Livewire::actingAs($otherUser)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('deleteConfirmation', $project->name)
            ->call('deleteProject');

        $this->assertDatabaseHas('project', [
            'id' => $project->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function test_url_validation()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('website', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['website']);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('issues', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['issues']);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType])
            ->set('source', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['source']);
    }

    #[Test]
    public function test_slug_does_not_auto_generate_when_editing()
    {
        $project = Project::factory()->owner($this->user)->create(['slug' => 'original-slug']);

        Livewire::actingAs($this->user)
            ->test(ProjectForm::class, ['projectType' => $this->projectType, 'project' => $project])
            ->set('name', 'Completely New Name')
            ->assertSet('slug', 'original-slug'); // Should NOT change
    }
}
