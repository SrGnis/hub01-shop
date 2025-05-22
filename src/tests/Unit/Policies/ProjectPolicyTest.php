<?php

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\Membership;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectPolicy $policy;
    protected User $user;
    protected Project $project;

    public function setUp(): void
    {
        parent::setUp();

        $this->policy = new ProjectPolicy();
        $this->user = User::factory()->create(['email_verified_at' => now()]);

        // Create a project type first
        $projectType = \App\Models\ProjectType::create([
            'value' => 'mod',
            'display_name' => 'Mod',
            'icon' => 'lucide-puzzle'
        ]);

        // Create project with the project type ID
        $this->project = Project::factory()->create([
            'project_type_id' => $projectType->id
        ]);
    }

    public function test_any_user_can_view_projects()
    {
        $this->assertTrue($this->policy->viewAny($this->user));
        $this->assertTrue($this->policy->viewAny(null)); // Guest user
    }

    public function test_any_user_can_view_a_project()
    {
        $this->assertTrue($this->policy->view($this->user, $this->project));
        $this->assertTrue($this->policy->view(null, $this->project)); // Guest user
    }

    public function test_verified_user_can_create_project()
    {
        $this->assertTrue($this->policy->create($this->user));

        // Unverified user cannot create project
        $unverifiedUser = User::factory()->create(['email_verified_at' => null]);
        $this->assertFalse($this->policy->create($unverifiedUser));
    }

    public function test_project_member_can_update_project()
    {
        // User is not a member initially
        $this->assertFalse($this->policy->update($this->user, $this->project));

        // Make user a member
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        $this->assertTrue($this->policy->update($this->user, $this->project));
    }

    public function test_project_owner_can_delete_project()
    {
        // User is not an owner initially
        $this->assertFalse($this->policy->delete($this->user, $this->project));

        // Make user a regular member
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        // Regular member cannot delete
        $this->assertFalse($this->policy->delete($this->user, $this->project));

        // Update to primary owner
        $membership->primary = true;
        $membership->role = 'owner';
        $membership->save();

        // Primary owner can delete
        $this->assertTrue($this->policy->delete($this->user, $this->project));
    }

    public function test_project_member_can_add_members()
    {
        // User is not a member initially
        $this->assertFalse($this->policy->addMember($this->user, $this->project));

        // Make user a member
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        // Member can add other members
        $this->assertTrue($this->policy->addMember($this->user, $this->project));
    }

    public function test_project_owner_can_remove_members()
    {
        // User is not an owner initially
        $this->assertFalse($this->policy->removeMember($this->user, $this->project));

        // Make user a regular member
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        // Regular member cannot remove members
        $this->assertFalse($this->policy->removeMember($this->user, $this->project));

        // Update to primary owner
        $membership->primary = true;
        $membership->role = 'owner';
        $membership->save();

        // Primary owner can remove members
        $this->assertTrue($this->policy->removeMember($this->user, $this->project));
    }

    public function test_project_member_can_edit_project()
    {
        // User is not a member initially
        $this->assertFalse($this->policy->edit($this->user, $this->project));

        // Make user a member
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        // Member can edit the project
        $this->assertTrue($this->policy->edit($this->user, $this->project));
    }

    public function test_project_owner_can_restore_project()
    {
        // User is not an owner initially
        $this->assertFalse($this->policy->restore($this->user, $this->project));

        // Make user a regular member
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        // Regular member cannot restore
        $this->assertFalse($this->policy->restore($this->user, $this->project));

        // Update to primary owner
        $membership->primary = true;
        $membership->role = 'owner';
        $membership->save();

        // Primary owner can restore
        $this->assertTrue($this->policy->restore($this->user, $this->project));
    }

    public function test_project_owner_can_force_delete_project()
    {
        // User is not an owner initially
        $this->assertFalse($this->policy->forceDelete($this->user, $this->project));

        // Make user a regular member
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        // Regular member cannot force delete
        $this->assertFalse($this->policy->forceDelete($this->user, $this->project));

        // Update to primary owner
        $membership->primary = true;
        $membership->role = 'owner';
        $membership->save();

        // Primary owner can force delete
        $this->assertTrue($this->policy->forceDelete($this->user, $this->project));
    }

    public function test_project_member_can_upload_version()
    {
        // User is not a member initially
        $this->assertFalse($this->policy->uploadVersion($this->user, $this->project));

        // Make user a member
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        // Member can upload versions
        $this->assertTrue($this->policy->uploadVersion($this->user, $this->project));
    }

    public function test_project_member_can_edit_version()
    {
        // User is not a member initially
        $this->assertFalse($this->policy->editVersion($this->user, $this->project));

        // Make user a member
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        // Member can edit versions
        $this->assertTrue($this->policy->editVersion($this->user, $this->project));
    }

    public function test_rejected_member_cannot_perform_actions()
    {
        // Create a rejected membership
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'rejected'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        // Rejected member cannot perform any member actions
        $this->assertFalse($this->policy->update($this->user, $this->project));
        $this->assertFalse($this->policy->edit($this->user, $this->project));
        $this->assertFalse($this->policy->addMember($this->user, $this->project));
        $this->assertFalse($this->policy->uploadVersion($this->user, $this->project));
        $this->assertFalse($this->policy->editVersion($this->user, $this->project));
    }

    public function test_pending_member_cannot_perform_actions()
    {
        // Create a pending membership
        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'pending'
        ]);
        $membership->user()->associate($this->user);
        $membership->project()->associate($this->project);
        $membership->save();

        // Pending member cannot perform any member actions
        $this->assertFalse($this->policy->update($this->user, $this->project));
        $this->assertFalse($this->policy->edit($this->user, $this->project));
        $this->assertFalse($this->policy->addMember($this->user, $this->project));
        $this->assertFalse($this->policy->uploadVersion($this->user, $this->project));
        $this->assertFalse($this->policy->editVersion($this->user, $this->project));
    }
}
