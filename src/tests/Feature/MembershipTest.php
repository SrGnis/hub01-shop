<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $invitedUser;

    protected ProjectType $projectType;

    protected Project $project;

    protected Membership $pendingMembership;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a project type
        $this->projectType = ProjectType::firstOrCreate([
            'value' => 'mod',
            'display_name' => 'Mod',
            'icon' => 'lucide-puzzle',
        ]);

        // Create a project owner
        $this->owner = User::factory()->create([
            'name' => 'Project Owner',
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
        ]);

        // Create a project
        $projectName = 'Test Project';
        $this->project = Project::factory()->create([
            'name' => $projectName,
            'slug' => Str::slug($projectName),
            'project_type_id' => $this->projectType->id,
        ]);

        // Create owner membership
        $ownerMembership = new Membership([
            'role' => 'owner',
            'primary' => true,
            'status' => 'active',
        ]);
        $ownerMembership->user()->associate($this->owner);
        $ownerMembership->project()->associate($this->project);
        $ownerMembership->save();

        // Create a user to invite
        $this->invitedUser = User::factory()->create([
            'name' => 'Invited User',
            'email' => 'invited@example.com',
            'email_verified_at' => now(),
        ]);

        // Create a pending membership invitation
        $this->pendingMembership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'pending',
        ]);
        $this->pendingMembership->user()->associate($this->invitedUser);
        $this->pendingMembership->project()->associate($this->project);
        $this->pendingMembership->inviter()->associate($this->owner);
        $this->pendingMembership->save();
    }

    #[Test]
    public function membership_accept_route_works_correctly()
    {
        // Generate a signed URL for accepting the invitation
        $url = URL::signedRoute('membership.accept', ['membership' => $this->pendingMembership->id]);

        // Visit the accept URL
        $response = $this->actingAs($this->invitedUser)->get($url);

        // Should redirect to the project page
        $response->assertRedirect(route('project.show', [
            'projectType' => $this->projectType->value,
            'project' => $this->project->slug,
        ]));

        // Membership status should be updated to 'active'
        $this->assertEquals('active', $this->pendingMembership->fresh()->status);
    }

    #[Test]
    public function membership_reject_route_works_correctly()
    {
        // Generate a signed URL for rejecting the invitation
        $url = URL::signedRoute('membership.reject', ['membership' => $this->pendingMembership->id]);

        // Visit the reject URL
        $response = $this->actingAs($this->invitedUser)->get($url);

        // Should redirect to the project search page
        $response->assertRedirect(route('project-search', ['projectType' => 'mod']));

        // Membership status should be updated to 'rejected'
        $this->assertEquals('rejected', $this->pendingMembership->fresh()->status);
    }

    #[Test]
    public function unsigned_membership_urls_are_rejected()
    {
        // Try to access the accept route without a signature
        $this->get(route('membership.accept', ['membership' => $this->pendingMembership->id]))
            ->assertStatus(403);

        // Try to access the reject route without a signature
        $this->get(route('membership.reject', ['membership' => $this->pendingMembership->id]))
            ->assertStatus(403);
    }
}
