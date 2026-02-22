<?php

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectType = ProjectType::factory()->create();
    }

    #[Test]
    public function test_get_projects_returns_paginated_list()
    {
        Project::factory()->count(15)->owner(User::factory()->create())->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.projects', ['project_type' => $this->projectType->value]));

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
    public function test_get_projects_returns_empty_array_when_none_exist()
    {
        $response = $this->getJson(route('api.v1.projects', ['project_type' => $this->projectType->value]));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    #[Test]
    public function test_get_projects_applies_default_filters()
    {
        Project::factory()->count(5)->owner(User::factory()->create())->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.projects', ['project_type' => $this->projectType->value]));

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(1, $meta['current_page']);
    }

    #[Test]
    public function test_search_query_filters_projects_by_name()
    {
        $user = User::factory()->create();

        $project1 = Project::factory()->owner($user)->create([
            'name' => 'Amazing Project',
            'project_type_id' => $this->projectType->id,
        ]);

        $project2 = Project::factory()->owner($user)->create([
            'name' => 'Another Cool Thing',
            'project_type_id' => $this->projectType->id,
        ]);

        $project3 = Project::factory()->owner($user)->create([
            'name' => 'Something Different',
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.projects', ['project_type' => $this->projectType->value, 'search' => 'Amazing']));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($project1->slug, $data[0]['slug']);
    }

    #[Test]
    public function test_project_type_filter_by_slug()
    {
        $user = User::factory()->create();
        $otherProjectType = ProjectType::factory()->create();

        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Correct Type Project',
        ]);

        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $otherProjectType->id,
            'name' => 'Wrong Type Project',
        ]);

        $response = $this->getJson(route('api.v1.projects', ['project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($project1->slug, $data[0]['slug']);
    }

    #[Test]
    public function test_project_tags_filter()
    {
        $user = User::factory()->create();

        $tag1 = ProjectTag::factory()->create();
        $tag1->projectTypes()->attach($this->projectType);

        $tag2 = ProjectTag::factory()->create();
        $tag2->projectTypes()->attach($this->projectType);

        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project with Tag 1',
        ]);
        $project1->tags()->attach($tag1);

        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project with Tag 2',
        ]);
        $project2->tags()->attach($tag2);

        $response = $this->getJson(route('api.v1.projects', ['tags' => [$tag1->slug], 'project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($project1->slug, $data[0]['slug']);
    }

    #[Test]
    public function test_multiple_project_tags_filter()
    {
        $user = User::factory()->create();

        $tag1 = ProjectTag::factory()->create();
        $tag1->projectTypes()->attach($this->projectType);

        $tag2 = ProjectTag::factory()->create();
        $tag2->projectTypes()->attach($this->projectType);

        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project with Both Tags',
        ]);
        $project1->tags()->attach([$tag1, $tag2]);

        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project with One Tag',
        ]);
        $project2->tags()->attach($tag1);

        $response = $this->getJson(route('api.v1.projects', ['tags' => [$tag1->slug, $tag2->slug], 'project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($project1->slug, $data[0]['slug']);
    }

    #[Test]
    public function test_version_tags_filter()
    {
        $user = User::factory()->create();

        $versionTag = ProjectVersionTag::factory()->create();
        $versionTag->projectTypes()->attach($this->projectType);

        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project with Version Tag',
        ]);
        $version1 = $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);
        $version1->tags()->attach($versionTag);

        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project without Version Tag',
        ]);

        $response = $this->getJson(route('api.v1.projects', ['version_tags' => [$versionTag->slug], 'project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($project1->slug, $data[0]['slug']);
    }

    #[Test]
    public function test_order_by_name()
    {
        $user = User::factory()->create();

        $projectA = Project::factory()->owner($user)->create([
            'name' => 'Alpha Project',
            'project_type_id' => $this->projectType->id,
        ]);

        $projectZ = Project::factory()->owner($user)->create([
            'name' => 'Zeta Project',
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.projects', ['order_by' => 'name', 'order_direction' => 'asc', 'project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Alpha Project', $data[0]['name']);
        $this->assertEquals('Zeta Project', $data[1]['name']);
    }

    #[Test]
    public function test_order_by_created_at()
    {
        $user = User::factory()->create();

        $projectOld = Project::factory()->owner($user)->create([
            'name' => 'Old Project',
            'project_type_id' => $this->projectType->id,
            'created_at' => now()->subDays(10),
        ]);

        $projectNew = Project::factory()->owner($user)->create([
            'name' => 'New Project',
            'project_type_id' => $this->projectType->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson(route('api.v1.projects', ['order_by' => 'created_at', 'order_direction' => 'desc', 'project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('New Project', $data[0]['name']);
        $this->assertEquals('Old Project', $data[1]['name']);
    }

    #[Test]
    public function test_order_by_downloads()
    {
        $user = User::factory()->create();

        $projectLow = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Low Downloads Project',
        ]);
        $versionLow = $projectLow->versions()->create([
            'name' => 'Low Downloads Version',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'downloads' => 10,
        ]);

        $projectHigh = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'High Downloads Project',
        ]);
        $versionHigh = $projectHigh->versions()->create([
            'name' => 'High Downloads Version',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'downloads' => 1000,
        ]);

        $response = $this->getJson(route('api.v1.projects', ['order_by' => 'downloads', 'order_direction' => 'desc', 'project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($projectHigh->slug, $data[0]['slug']);
        $this->assertEquals($projectLow->slug, $data[1]['slug']);
    }

    #[Test]
    public function test_per_page_filter()
    {
        Project::factory()->count(30)->owner(User::factory()->create())->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.projects', ['per_page' => 25, 'project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $this->assertCount(25, $response->json('data'));
        $this->assertEquals(30, $response->json('meta.total'));
    }

    #[Test]
    public function test_invalid_per_page_returns_validation_error()
    {
        $response = $this->getJson(route('api.v1.projects', ['per_page' => 101, 'project_type' => $this->projectType->value]));

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJsonValidationErrors(['per_page']);
    }

    #[Test]
    public function test_invalid_order_by_returns_validation_error()
    {
        $response = $this->getJson(route('api.v1.projects', ['order_by' => 'invalid', 'project_type' => $this->projectType->value]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_by']);
    }

    #[Test]
    public function test_invalid_order_direction_returns_validation_error()
    {
        $response = $this->getJson(route('api.v1.projects', ['order_direction' => 'invalid', 'project_type' => $this->projectType->value]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_direction']);
    }

    #[Test]
    public function test_release_date_period_last_30_days()
    {
        $user = User::factory()->create();

        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 15 days ago',
        ]);
        $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(15),
            'release_type' => 'release',
        ]);

        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 45 days ago',
        ]);
        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(45),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.projects', ['release_date_period' => 'last_30_days', 'project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($project1->slug, $data[0]['slug']);
    }

    #[Test]
    public function test_release_date_period_last_90_days()
    {
        $user = User::factory()->create();

        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 45 days ago',
        ]);
        $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(45),
            'release_type' => 'release',
        ]);

        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 100 days ago',
        ]);
        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(100),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.projects', ['release_date_period' => 'last_90_days', 'project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($project1->slug, $data[0]['slug']);
    }

    #[Test]
    public function test_release_date_period_last_year()
    {
        $user = User::factory()->create();

        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 6 months ago',
        ]);
        $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subMonths(6),
            'release_type' => 'release',
        ]);

        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 14 months ago',
        ]);
        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subMonths(14),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.projects', ['release_date_period' => 'last_year', 'project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($project1->slug, $data[0]['slug']);
    }

    #[Test]
    public function test_release_date_period_custom()
    {
        $user = User::factory()->create();

        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project January 2024',
        ]);
        $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => \Carbon\Carbon::parse('2024-01-15'),
            'release_type' => 'release',
        ]);

        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project February 2024',
        ]);
        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => \Carbon\Carbon::parse('2024-02-15'),
            'release_type' => 'release',
        ]);

        $project3 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project March 2024',
        ]);
        $project3->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => \Carbon\Carbon::parse('2024-03-15'),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.projects', [
            'release_date_period' => 'custom',
            'release_date_start' => '2024-01-01',
            'release_date_end' => '2024-02-28',
            'project_type' => $this->projectType->value,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $slugs = collect($data)->pluck('slug')->toArray();
        $this->assertContains($project1->slug, $slugs);
        $this->assertContains($project2->slug, $slugs);
        $this->assertNotContains($project3->slug, $slugs);
    }

    #[Test]
    public function test_invalid_release_date_period_returns_validation_error()
    {
        $response = $this->getJson(route('api.v1.projects', ['release_date_period' => 'invalid_period']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['release_date_period']);
    }

    #[Test]
    public function test_invalid_date_format_returns_validation_error()
    {
        $response = $this->getJson(route('api.v1.projects', [
            'release_date_period' => 'custom',
            'release_date_start' => 'invalid-date',
            'release_date_end' => '2024-02-28',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['release_date_start']);
    }

    #[Test]
    public function test_release_date_end_before_start_returns_validation_error()
    {
        $response = $this->getJson(route('api.v1.projects', [
            'release_date_period' => 'custom',
            'release_date_start' => '2024-02-01',
            'release_date_end' => '2024-01-31',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['release_date_end']);
    }

    #[Test]
    public function test_nonexistent_project_type_returns_validation_error()
    {
        $response = $this->getJson(route('api.v1.projects', ['project_type' => 'nonexistent']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_type']);
    }

    #[Test]
    public function test_nonexistent_project_tags_returns_validation_error()
    {
        $response = $this->getJson(route('api.v1.projects', ['tags' => ['nonexistent']]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags.0']);
    }

    #[Test]
    public function test_nonexistent_version_tags_returns_validation_error()
    {
        $response = $this->getJson(route('api.v1.projects', ['version_tags' => ['nonexistent']]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['version_tags.0']);
    }

    #[Test]
    public function test_combined_filters_work_together()
    {
        $user = User::factory()->create();

        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Amazing Project with Tag',
        ]);
        $project1->tags()->attach($tag);
        $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(15),
            'release_type' => 'release',
        ]);

        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Another Project',
        ]);
        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(45),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.projects', [
            'project_type' => $this->projectType->value,
            'tags' => [$tag->slug],
            'release_date_period' => 'last_30_days',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($project1->slug, $data[0]['slug']);
    }

    #[Test]
    public function test_only_approved_projects_are_shown()
    {
        $user = User::factory()->create();

        $approvedProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Approved Project',
        ]);

        $draftProject = Project::factory()->owner($user)->draft()->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Draft Project',
        ]);

        $pendingProject = Project::factory()->owner($user)->pending()->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Pending Project',
        ]);

        $response = $this->getJson(route('api.v1.projects', ['project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $slugs = collect($data)->pluck('slug')->toArray();
        $this->assertContains($approvedProject->slug, $slugs);
        $this->assertNotContains($draftProject->slug, $slugs);
        $this->assertNotContains($pendingProject->slug, $slugs);
    }

    #[Test]
    public function test_deactivated_projects_are_hidden()
    {
        $user = User::factory()->create();

        $activeProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Active Project',
        ]);

        $deactivatedProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'deactivated_at' => now(),
            'name' => 'Deactivated Project',
        ]);

        $response = $this->getJson(route('api.v1.projects', ['project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $slugs = collect($data)->pluck('slug')->toArray();
        $this->assertContains($activeProject->slug, $slugs);
        $this->assertNotContains($deactivatedProject->slug, $slugs);
    }

    #[Test]
    public function test_description_excluded_in_collection_responses()
    {
        $user = User::factory()->create();

        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'description' => 'This is a description',
        ]);

        $response = $this->getJson(route('api.v1.projects', ['project_type' => $this->projectType->value]));

        $response->assertStatus(200);
        $data = $response->json('data.0');
        $this->assertNull($data['description']);
    }

    #[Test]
    public function test_get_project_by_valid_slug()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'name' => 'Test Project',
            'summary' => 'A test project summary',
            'description' => 'A test project description',
        ]);

        $response = $this->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'slug',
                    'summary',
                    'description',
                    'logo_url',
                    'website',
                    'issues',
                    'source',
                    'type',
                    'tags',
                    'status',
                    'members',
                    'downloads',
                    'last_release_date',
                    'version_count',
                    'created_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Test Project',
                    'slug' => $project->slug,
                    'summary' => 'A test project summary',
                    'description' => 'A test project description',
                ],
            ]);
    }

    #[Test]
    public function test_get_project_by_invalid_slug_returns_404()
    {
        $response = $this->getJson(route('api.v1.project', ['slug' => 'invalid-slug']));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found',
            ]);
    }

    #[Test]
    public function test_project_single_resource_includes_description()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'description' => 'This is a description',
        ]);

        $response = $this->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('description', $data);
        $this->assertEquals('This is a description', $data['description']);
    }

    #[Test]
    public function test_project_single_resource_includes_members()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        $member = User::factory()->create();
        Membership::factory()->create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => 'contributor',
            'status' => 'active',
        ]);

        $response = $this->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('members', $data);
        $this->assertIsArray($data['members']);
        $this->assertCount(2, $data['members']);
    }

    #[Test]
    public function test_project_single_resource_includes_external_credits()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        $project->externalCredits()->createMany([
            [
                'name' => 'Jane Doe',
                'role' => 'Composer',
                'url' => 'https://example.com/jane',
            ],
            [
                'name' => 'John Roe',
                'role' => 'Concept Artist',
                'url' => null,
            ],
        ]);

        $response = $this->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertArrayHasKey('external_credits', $data);
        $this->assertIsArray($data['external_credits']);
        $this->assertCount(2, $data['external_credits']);
        $this->assertEquals('Jane Doe', $data['external_credits'][0]['name']);
        $this->assertEquals('Composer', $data['external_credits'][0]['role']);
        $this->assertEquals('https://example.com/jane', $data['external_credits'][0]['url']);
        $this->assertEquals('John Roe', $data['external_credits'][1]['name']);
        $this->assertNull($data['external_credits'][1]['url']);
    }

    #[Test]
    public function test_project_single_resource_includes_tags()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);
        $project->tags()->attach($tag);

        $response = $this->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('tags', $data);
        $this->assertIsArray($data['tags']);
        $this->assertContains($tag->slug, $data['tags']);
    }

    #[Test]
    public function test_approved_project_accessible_to_all()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        $response = $this->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(200);
    }

    #[Test]
    public function test_draft_project_accessible_only_to_member()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->owner($owner)->draft()->create();

        $response = $this->actingAs($owner)
            ->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(200);

        $response = $this->actingAs($otherUser)
            ->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(404);
    }

    #[Test]
    public function test_pending_project_accessible_only_to_member()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->owner($owner)->pending()->create();

        $response = $this->actingAs($owner)
            ->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(200);

        $response = $this->actingAs($otherUser)
            ->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(404);
    }

    #[Test]
    public function test_deactivated_project_returns_404()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'deactivated_at' => now(),
        ]);

        $response = $this->getJson(route('api.v1.project', ['slug' => $project->slug]));

        $response->assertStatus(404);
    }
}
