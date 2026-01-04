<?php

namespace Tests\Feature\User\Livewire;

use App\Livewire\UserProfile;
use App\Models\Project;
use App\Models\User;
use App\Models\Membership;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_user_profile_component_renders()
    {
        $user = User::factory()->create();
        
        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->assertOk()
            ->assertViewIs('livewire.user-profile');
    }

    #[Test]
    public function test_active_projects_computed_property()
    {
        $user = User::factory()->create();
        
        // Create 2 active projects owned by user
        $project1 = Project::factory()->owner($user)->create(['created_at' => now()->subDay()]);
        $project2 = Project::factory()->owner($user)->create(['created_at' => now()]);
        
        // Create 1 project NOT owned by user
        Project::factory()->create();

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->assertCount('activeProjects', 2);
    }

    #[Test]
    public function test_deleted_projects_computed_property_only_for_owner()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        // Create deleted project owned by user
        $project1 = Project::factory()->owner($user)->create();
        $project1->delete();
        
        // Create active project
        Project::factory()->owner($user)->create();

        // 1. Authenticated as Owner -> Should see deleted projects
        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->assertCount('deletedProjects', 1);

        // 2. Authenticated as Other User -> Should NOT see deleted projects
        Livewire::actingAs($otherUser)
            ->test(UserProfile::class, ['user' => $user])
            ->assertCount('deletedProjects', 0);
    }

    #[Test]
    public function test_owned_projects_count()
    {
        $user = User::factory()->create();
        
        // 2 Owned projects
        Project::factory()->owner($user)->create();
        Project::factory()->owner($user)->create();
        
        // 1 Contribution (not owned)
        $project3 = Project::factory()->create();
        $membership = new Membership([
            'role' => 'editor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($user);
        $membership->project()->associate($project3);
        $membership->save();

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->assertSet('ownedProjectsCount', 2);
    }

    #[Test]
    public function test_contributions_count()
    {
        $user = User::factory()->create();
        
        // 1 Owned project
        Project::factory()->owner($user)->create();
        
        // 2 Contributions (not owned)
        $project2 = Project::factory()->create();
        $membership = new Membership([
            'role' => 'editor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($user);
        $membership->project()->associate($project2);
        $membership->save();
        
        $project3 = Project::factory()->create();
        $membership = new Membership([
            'role' => 'viewer',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($user);
        $membership->project()->associate($project3);
        $membership->save();

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->assertSet('contributionsCount', 2);
    }

    #[Test]
    public function test_restore_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();
        $project->delete();

        $this->assertTrue($project->fresh()->trashed());
        
        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->call('restoreProject', $project->id)
            ->assertHasNoErrors();

        $this->assertFalse($project->fresh()->trashed());
    }

    #[Test]
    public function test_restore_project_authorization()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->owner($owner)->create();
        $project->delete();

        Livewire::actingAs($otherUser)
            ->test(UserProfile::class, ['user' => $owner])
            ->call('restoreProject', $project->id);

        $this->assertTrue($project->fresh()->trashed());
    }
}
