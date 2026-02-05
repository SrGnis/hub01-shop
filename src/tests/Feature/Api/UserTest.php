<?php

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectType = ProjectType::factory()->create();
    }

    #[Test]
    public function test_get_user_by_valid_name()
    {
        $user = User::factory()->create([
            'name' => 'testuser',
            'bio' => 'A test user bio',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        $response = $this->getJson('/api/v1/user/testuser');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'username',
                    'bio',
                    'avatar',
                    'created_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'username' => 'testuser',
                    'bio' => 'A test user bio',
                    // Avatar is null because getAvatarUrl() checks if file exists in storage
                    'avatar' => null,
                ],
            ]);
    }

    #[Test]
    public function test_get_user_by_invalid_name_returns_404()
    {
        $response = $this->getJson('/api/v1/user/nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found',
            ]);
    }

    #[Test]
    public function test_user_response_has_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->getJson('/api/v1/user/' . $user->name);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('bio', $data);
        $this->assertArrayHasKey('avatar', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertIsString($data['username']);
        $this->assertIsString($data['created_at']);
    }

    #[Test]
    public function test_user_bio_can_be_null()
    {
        $user = User::factory()->create([
            'bio' => null,
        ]);

        $response = $this->getJson('/api/v1/user/' . $user->name);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNull($data['bio']);
    }

    #[Test]
    public function test_user_avatar_can_be_null()
    {
        $user = User::factory()->create([
            'avatar' => null,
        ]);

        $response = $this->getJson('/api/v1/user/' . $user->name);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNull($data['avatar']);
    }

    #[Test]
    public function test_get_user_projects_returns_paginated_list()
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 15; $i++) {
            $project = Project::factory()->owner($user)->create([
                'project_type_id' => $this->projectType->id,
                'name' => "Project $i",
            ]);
            Membership::factory()->create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
            ]);
        }

        $response = $this->getJson('/api/v1/user/' . $user->name . '/projects');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(15, $response->json('meta.total'));
    }

    #[Test]
    public function test_get_user_projects_with_valid_username()
    {
        $user = User::factory()->create();

        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        Membership::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/user/' . $user->name . '/projects');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function test_get_user_projects_with_invalid_username_returns_404()
    {
        $response = $this->getJson('/api/v1/user/nonexistent/projects');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User not found',
            ]);
    }

    #[Test]
    public function test_only_returns_projects_with_active_membership()
    {
        $user = User::factory()->create();
        $other_user = User::factory()->create();

        $activeProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Active Project',
        ]);
        Membership::factory()->create([
            'project_id' => $activeProject->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $rejectedProject = Project::factory()->owner($other_user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Rejected Project',
        ]);
        Membership::factory()->create([
            'project_id' => $rejectedProject->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'rejected',
        ]);

        $response = $this->getJson('/api/v1/user/' . $user->name . '/projects');

        $response->assertStatus(200);
        $data = $response->json('data');
        $slugs = collect($data)->pluck('slug')->toArray();
        $this->assertContains($activeProject->slug, $slugs);
        $this->assertNotContains($rejectedProject->slug, $slugs);
    }

    #[Test]
    public function test_user_projects_excludes_description()
    {
        $user = User::factory()->create();

        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'description' => 'This is a description',
        ]);
        Membership::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/user/' . $user->name . '/projects');

        $response->assertStatus(200);
        $data = $response->json('data.0');
        $this->assertNull($data['description']);
    }

    #[Test]
    public function test_user_projects_response_has_required_fields()
    {
        $user = User::factory()->create();

        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        Membership::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/user/' . $user->name . '/projects');

        $response->assertStatus(200);
        $data = $response->json('data.0');

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('logo_url', $data);
        $this->assertArrayHasKey('website', $data);
        $this->assertArrayHasKey('issues', $data);
        $this->assertArrayHasKey('source', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('tags', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('downloads', $data);
        $this->assertArrayHasKey('last_release_date', $data);
        $this->assertArrayHasKey('version_count', $data);
        $this->assertArrayHasKey('created_at', $data);
    }

    #[Test]
    public function test_user_projects_pagination_metadata_is_correct()
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 25; $i++) {
            $project = Project::factory()->owner($user)->create([
                'project_type_id' => $this->projectType->id,
            ]);
            Membership::factory()->create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
            ]);
        }

        $response = $this->getJson('/api/v1/user/' . $user->name . '/projects');

        $response->assertStatus(200);
        $meta = $response->json('meta');

        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(1, $meta['from']);
        $this->assertEquals(3, $meta['last_page']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(10, $meta['to']);
        $this->assertEquals(25, $meta['total']);
    }

    #[Test]
    public function test_user_projects_does_not_include_projects_without_membership()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'User Project',
        ]);
        Membership::factory()->create([
            'project_id' => $userProject->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $otherProject = Project::factory()->owner($otherUser)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Other User Project',
        ]);

        $response = $this->getJson('/api/v1/user/' . $user->name . '/projects');

        $response->assertStatus(200);
        $data = $response->json('data');
        $slugs = collect($data)->pluck('slug')->toArray();
        $this->assertContains($userProject->slug, $slugs);
        $this->assertNotContains($otherProject->slug, $slugs);
    }
}
