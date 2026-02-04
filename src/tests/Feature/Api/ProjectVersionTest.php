<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDependency;
use App\Models\ProjectVersionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectVersionTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectType = ProjectType::factory()->create();
    }

    #[Test]
    public function test_get_project_versions_returns_paginated_list()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        for ($i = 1; $i <= 15; $i++) {
            $project->versions()->create([
                'name' => "Version 1.0.$i",
                'version' => "1.0.$i",
                'release_date' => now()->subDays($i),
                'release_type' => 'release',
            ]);
        }

        $response = $this->getJson(route('api.v1.project_versions', ['slug' => $project->slug]));

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
    public function test_get_project_versions_with_valid_project_slug()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', ['slug' => $project->slug]));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function test_get_project_versions_with_invalid_project_slug_returns_404()
    {
        $response = $this->getJson(route('api.v1.project_versions', ['slug' => 'invalid-slug']));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found',
            ]);
    }

    #[Test]
    public function test_get_project_versions_applies_default_filters()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', ['slug' => $project->slug]));

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(1, $meta['current_page']);
    }

    #[Test]
    public function test_version_tags_filter()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $versionTag = ProjectVersionTag::factory()->create();
        $versionTag->projectTypes()->attach($this->projectType);

        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);
        $version1->tags()->attach($versionTag);

        $version2 = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'tags' => [$versionTag->slug],
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($version1->version, $data[0]['version']);
    }

    #[Test]
    public function test_multiple_version_tags_filter()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $versionTag1 = ProjectVersionTag::factory()->create();
        $versionTag1->projectTypes()->attach($this->projectType);

        $versionTag2 = ProjectVersionTag::factory()->create();
        $versionTag2->projectTypes()->attach($this->projectType);

        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);
        $version1->tags()->attach([$versionTag1, $versionTag2]);

        $version2 = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);
        $version2->tags()->attach($versionTag1);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'tags' => [$versionTag1->slug, $versionTag2->slug],
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($version1->version, $data[0]['version']);
    }

    #[Test]
    public function test_order_by_version()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $version2 = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'order_by' => 'version',
            'order_direction' => 'asc',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('1.0.0', $data[0]['version']);
        $this->assertEquals('2.0.0', $data[1]['version']);
    }

    #[Test]
    public function test_order_by_release_date()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $oldVersion = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(10),
            'release_type' => 'release',
        ]);

        $newVersion = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'order_by' => 'release_date',
            'order_direction' => 'desc',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($newVersion->version, $data[0]['version']);
        $this->assertEquals($oldVersion->version, $data[1]['version']);
    }

    #[Test]
    public function test_order_by_downloads()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $versionLow = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'downloads' => 10,
        ]);

        $versionHigh = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'downloads' => 1000,
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'order_by' => 'downloads',
            'order_direction' => 'desc',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($versionHigh->version, $data[0]['version']);
        $this->assertEquals($versionLow->version, $data[1]['version']);
    }

    #[Test]
    public function test_per_page_filter()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        for ($i = 1; $i <= 30; $i++) {
            $project->versions()->create([
                'name' => "Version 1.0.$i",
                'version' => "1.0.$i",
                'release_date' => now()->subDays($i),
                'release_type' => 'release',
            ]);
        }

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'per_page' => 25,
        ]));

        $response->assertStatus(200);
        $this->assertCount(25, $response->json('data'));
        $this->assertEquals(30, $response->json('meta.total'));
    }

    #[Test]
    public function test_invalid_per_page_returns_validation_error()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'per_page' => 101,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    #[Test]
    public function test_invalid_order_by_returns_validation_error()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'order_by' => 'invalid_field',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_by']);
    }

    #[Test]
    public function test_invalid_order_direction_returns_validation_error()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'order_direction' => 'invalid_direction',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_direction']);
    }

    #[Test]
    public function test_release_date_period_last_30_days()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(15),
            'release_type' => 'release',
        ]);

        $version2 = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now()->subDays(45),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'release_date_period' => 'last_30_days',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($version1->version, $data[0]['version']);
    }

    #[Test]
    public function test_release_date_period_last_90_days()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(45),
            'release_type' => 'release',
        ]);

        $version2 = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now()->subDays(100),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'release_date_period' => 'last_90_days',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($version1->version, $data[0]['version']);
    }

    #[Test]
    public function test_release_date_period_last_year()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subMonths(6),
            'release_type' => 'release',
        ]);

        $version2 = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now()->subMonths(14),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'release_date_period' => 'last_year',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($version1->version, $data[0]['version']);
    }

    #[Test]
    public function test_release_date_period_custom()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => \Carbon\Carbon::parse('2024-01-15'),
            'release_type' => 'release',
        ]);

        $version2 = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => \Carbon\Carbon::parse('2024-02-15'),
            'release_type' => 'release',
        ]);

        $version3 = $project->versions()->create([
            'name' => 'Version 3.0.0',
            'version' => '3.0.0',
            'release_date' => \Carbon\Carbon::parse('2024-03-15'),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'release_date_period' => 'custom',
            'release_date_start' => '2024-01-01',
            'release_date_end' => '2024-02-28',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $versions = collect($data)->pluck('version')->toArray();
        $this->assertContains($version1->version, $versions);
        $this->assertContains($version2->version, $versions);
        $this->assertNotContains($version3->version, $versions);
    }

    #[Test]
    public function test_invalid_release_date_period_returns_validation_error()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'release_date_period' => 'invalid_period',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['release_date_period']);
    }

    #[Test]
    public function test_invalid_date_format_returns_validation_error()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
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
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'release_date_period' => 'custom',
            'release_date_start' => '2024-02-01',
            'release_date_end' => '2024-01-31',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['release_date_end']);
    }

    #[Test]
    public function test_nonexistent_version_tags_returns_validation_error()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'tags' => ['nonexistent-tag'],
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags.0']);
    }

    #[Test]
    public function test_combined_filters_work_together()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $versionTag = ProjectVersionTag::factory()->create();
        $versionTag->projectTypes()->attach($this->projectType);

        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(30),
            'release_type' => 'release',
        ]);
        $version1->tags()->attach($versionTag);

        $version2 = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now()->subDays(60),
            'release_type' => 'release',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
            'release_date_period' => 'last_30_days',
            'tags' => [$versionTag->slug],
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($version1->version, $data[0]['version']);
    }

    #[Test]
    public function test_version_response_includes_relationships()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $versionTag = ProjectVersionTag::factory()->create();
        $versionTag->projectTypes()->attach($this->projectType);
        $version->tags()->attach($versionTag);

        $file = ProjectFile::factory()->create([
            'project_version_id' => $version->id,
        ]);

        $dependencyProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $dependencyVersion = $dependencyProject->versions()->create([
            'name' => 'Dependency Version',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        ProjectVersionDependency::factory()->specificVersion()->create([
            'project_version_id' => $version->id,
            'dependency_project_version_id' => $dependencyVersion->id,
            'dependency_type' => 'required',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data.0');

        $this->assertArrayHasKey('tags', $data);
        $this->assertArrayHasKey('files', $data);
        $this->assertArrayHasKey('dependencies', $data);
        $this->assertIsArray($data['tags']);
        $this->assertIsArray($data['files']);
        $this->assertIsArray($data['dependencies']);
    }

    #[Test]
    public function test_version_response_has_required_fields()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'changelog' => 'Test changelog',
        ]);

        $response = $this->getJson(route('api.v1.project_versions', [
            'slug' => $project->slug,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data.0');

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('release_type', $data);
        $this->assertArrayHasKey('release_date', $data);
        $this->assertArrayHasKey('changelog', $data);
        $this->assertArrayHasKey('downloads', $data);
        $this->assertArrayHasKey('tags', $data);
        $this->assertArrayHasKey('files', $data);
        $this->assertArrayHasKey('dependencies', $data);
    }

    #[Test]
    public function test_get_project_version_by_valid_slug()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'changelog' => 'Initial release',
        ]);

        $response = $this->getJson(route('api.v1.project_version', [
            'slug' => $project->slug,
            'version' => $version->version,
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'version',
                    'release_type',
                    'release_date',
                    'changelog',
                    'downloads',
                    'tags',
                    'files',
                    'dependencies',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Version 1.0.0',
                    'version' => '1.0.0',
                    'changelog' => 'Initial release',
                ],
            ]);
    }

    #[Test]
    public function test_get_project_version_by_invalid_project_slug_returns_404()
    {
        $response = $this->getJson(route('api.v1.project_version', [
            'slug' => 'invalid-slug',
            'version' => '1.0.0',
        ]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found',
            ]);
    }

    #[Test]
    public function test_get_project_version_by_invalid_version_returns_404()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->getJson(route('api.v1.project_version', [
            'slug' => $project->slug,
            'version' => 'invalid-version',
        ]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project version not found',
            ]);
    }

    #[Test]
    public function test_project_version_single_response_includes_tags()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $versionTag = ProjectVersionTag::factory()->create();
        $versionTag->projectTypes()->attach($this->projectType);
        $version->tags()->attach($versionTag);

        $response = $this->getJson(route('api.v1.project_version', [
            'slug' => $project->slug,
            'version' => $version->version,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('tags', $data);
        $this->assertIsArray($data['tags']);
        $this->assertContains($versionTag->slug, $data['tags']);
    }

    #[Test]
    public function test_project_version_single_response_includes_files()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $file = ProjectFile::factory()->create([
            'project_version_id' => $version->id,
        ]);

        $response = $this->getJson(route('api.v1.project_version', [
            'slug' => $project->slug,
            'version' => $version->version,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('files', $data);
        $this->assertIsArray($data['files']);
        $this->assertCount(1, $data['files']);
    }

    #[Test]
    public function test_project_version_single_response_includes_dependencies()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $dependencyProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $dependencyVersion = $dependencyProject->versions()->create([
            'name' => 'Dependency Version',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        ProjectVersionDependency::factory()->specificVersion()->create([
            'project_version_id' => $version->id,
            'dependency_project_version_id' => $dependencyVersion->id,
            'dependency_type' => 'required',
        ]);

        $response = $this->getJson(route('api.v1.project_version', [
            'slug' => $project->slug,
            'version' => $version->version,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('dependencies', $data);
        $this->assertIsArray($data['dependencies']);
        $this->assertCount(1, $data['dependencies']);
    }
}
