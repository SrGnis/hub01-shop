<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectQuota;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDependency;
use App\Models\ProjectVersionTag;
use App\Models\User;
use App\Models\UserQuota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    // =====================================================
    // POST /api/v1/project/{slug}/versions - Store Tests
    // =====================================================

    #[Test]
    public function test_store_version_without_token_returns_unauthorized()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->postJson(route('api.v1.project_version.store', [
            'slug' => $project->slug,
        ]));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_store_version_with_invalid_token_returns_unauthorized()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $response = $this->withToken('invalid-token-12345')
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_store_version_with_expired_token_returns_unauthorized()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $token = $user->createToken('test-token', ['*'], now()->subDay())->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_store_version_by_non_owner_returns_forbidden()
    {
        $owner = User::factory()->create();
        $project = Project::factory()->owner($owner)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $otherUser = User::factory()->create();
        $token = $otherUser->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function test_store_version_with_missing_required_fields_returns_validation_error()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'version', 'release_type', 'release_date', 'files']);
    }

    #[Test]
    public function test_store_version_with_invalid_release_type_returns_validation_error()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'invalid',
                'release_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['release_type']);
    }

    #[Test]
    public function test_store_version_with_duplicate_version_returns_validation_error()
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

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['version']);
    }

    #[Test]
    public function test_store_version_with_nonexistent_project_returns_not_found()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => 'nonexistent-project',
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found',
            ]);
    }

    #[Test]
    public function test_store_version_successfully_with_single_file()
    {
        Storage::fake('projects');

        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        $file = UploadedFile::fake()->create('test-file.zip', 100);

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'changelog' => 'Initial release',
                'files' => [$file],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'version',
                    'release_type',
                    'release_date',
                    'changelog',
                    'files',
                    'tags',
                    'dependencies',
                ],
                'message',
            ])
            ->assertJson([
                'message' => 'Version created successfully',
            ]);

        // Verify database
        $this->assertDatabaseHas('project_version', [
            'project_id' => $project->id,
            'version' => '1.0.0',
            'name' => 'Version 1.0.0',
        ]);

        // Verify file was created
        $this->assertDatabaseHas('project_file', [
            'name' => 'test-file.zip',
        ]);
    }

    #[Test]
    public function test_store_version_successfully_with_multiple_files()
    {
        Storage::fake('projects');

        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        $files = [
            UploadedFile::fake()->create('file1.zip', 50),
            UploadedFile::fake()->create('file2.zip', 50),
            UploadedFile::fake()->create('file3.zip', 50),
        ];

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'files' => $files,
            ]);

        $response->assertStatus(201);

        // Verify all files were created
        $this->assertDatabaseHas('project_file', ['name' => 'file1.zip']);
        $this->assertDatabaseHas('project_file', ['name' => 'file2.zip']);
        $this->assertDatabaseHas('project_file', ['name' => 'file3.zip']);

        $version = ProjectVersion::where('version', '1.0.0')
            ->where('project_id', $project->id)
            ->first();
        $this->assertCount(3, $version->files);
    }

    #[Test]
    public function test_store_version_with_file_count_exceeding_quota_returns_validation_error()
    {
        Storage::fake('projects');

        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        // Set quota limit to 2 files per version
        ProjectQuota::factory()->filesPerVersion(2)->create([
            'project_id' => $project->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        $files = [
            UploadedFile::fake()->create('file1.zip', 10),
            UploadedFile::fake()->create('file2.zip', 10),
            UploadedFile::fake()->create('file3.zip', 10),
        ];

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'files' => $files,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['files']);
    }

    #[Test]
    public function test_store_version_with_file_size_exceeding_quota_returns_validation_error()
    {
        Storage::fake('projects');

        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        // Set file size limit to 50KB
        ProjectQuota::factory()->fileSize(50 * 1024)->create([
            'project_id' => $project->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        // Create file larger than quota (100KB)
        $file = UploadedFile::fake()->create('large-file.zip', 100);

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'files' => [$file],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['files.0']);
    }

    #[Test]
    public function test_store_version_with_tags()
    {
        Storage::fake('projects');

        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $tag1 = ProjectVersionTag::factory()->create();
        $tag1->projectTypes()->attach($this->projectType);
        $tag2 = ProjectVersionTag::factory()->create();
        $tag2->projectTypes()->attach($this->projectType);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        $file = UploadedFile::fake()->create('test-file.zip', 50);

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'files' => [$file],
                'tags' => [$tag1->slug, $tag2->slug],
            ]);

        $response->assertStatus(201);

        $version = ProjectVersion::where('version', '1.0.0')
            ->where('project_id', $project->id)
            ->first();

        $this->assertTrue($version->tags->contains($tag1));
        $this->assertTrue($version->tags->contains($tag2));
    }

    #[Test]
    public function test_store_version_with_subtags_automatically_includes_parent_tag()
    {
        Storage::fake('projects');

        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $parentTag = ProjectVersionTag::factory()->create();
        $parentTag->projectTypes()->attach($this->projectType);
        $subTag = ProjectVersionTag::factory()->create(['parent_id' => $parentTag->id]);
        $subTag->projectTypes()->attach($this->projectType);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        $file = UploadedFile::fake()->create('test-file.zip', 50);

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'files' => [$file],
                'tags' => [$subTag->slug], // Only specify subtag
            ]);

        $response->assertStatus(201);

        $version = ProjectVersion::where('version', '1.0.0')
            ->where('project_id', $project->id)
            ->first();

        // Both parent and subtag should be attached
        $this->assertTrue($version->tags->contains($parentTag));
        $this->assertTrue($version->tags->contains($subTag));
    }

    #[Test]
    public function test_store_version_with_invalid_tag_returns_validation_error()
    {
        Storage::fake('projects');

        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        $file = UploadedFile::fake()->create('test-file.zip', 50);

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'files' => [$file],
                'tags' => ['nonexistent-tag'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags.0']);
    }

    #[Test]
    public function test_store_version_with_required_dependency()
    {
        Storage::fake('projects');

        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
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

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        $file = UploadedFile::fake()->create('test-file.zip', 50);

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'files' => [$file],
                'dependencies' => [
                    [
                        'project' => $dependencyProject->slug,
                        'version' => $dependencyVersion->version,
                        'type' => 'required',
                        'external' => false,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $version = ProjectVersion::where('version', '1.0.0')
            ->where('project_id', $project->id)
            ->first();

        $this->assertCount(1, $version->dependencies);
        $dependency = $version->dependencies->first();
        $this->assertEquals('required', $dependency->dependency_type->value);
        $this->assertEquals($dependencyVersion->id, $dependency->dependency_project_version_id);
    }

    #[Test]
    public function test_store_version_with_external_manual_dependency()
    {
        Storage::fake('projects');

        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        $file = UploadedFile::fake()->create('test-file.zip', 50);

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.store', [
                'slug' => $project->slug,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'files' => [$file],
                'dependencies' => [
                    [
                        'type' => 'optional',
                        'external' => true,
                        'project' => 'dummy-project',
                        'version' => '2.0.0',
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $version = ProjectVersion::where('version', '1.0.0')
            ->where('project_id', $project->id)
            ->first();

        $this->assertCount(1, $version->dependencies);
        $dependency = $version->dependencies->first();
        $this->assertEquals('optional', $dependency->dependency_type->value);
        $this->assertEquals('dummy-project', $dependency->dependency_name);
        $this->assertEquals('2.0.0', $dependency->dependency_version);
    }

    // =====================================================
    // POST /api/v1/project/{slug}/version/{version} - Update Tests
    // =====================================================

    #[Test]
    public function test_update_version_without_token_returns_unauthorized()
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

        $response = $this->postJson(route('api.v1.project_version.update', [
            'slug' => $project->slug,
            'version' => $version->version,
        ]));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_update_version_with_invalid_token_returns_unauthorized()
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

        $response = $this->withToken('invalid-token-12345')
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_update_version_by_non_owner_returns_forbidden()
    {
        $owner = User::factory()->create();
        $project = Project::factory()->owner($owner)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $otherUser = User::factory()->create();
        $token = $otherUser->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), [
                'name' => 'Updated Version',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function test_update_version_with_missing_required_fields_returns_validation_error()
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

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'version', 'release_type', 'release_date']);
    }

    #[Test]
    public function test_update_version_with_nonexistent_project_returns_not_found()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => 'nonexistent-project',
                'version' => '1.0.0',
            ]), [
                'name' => 'Updated Version',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found',
            ]);
    }

    #[Test]
    public function test_update_version_with_nonexistent_version_returns_not_found()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => '999.0.0',
            ]), [
                'name' => 'Updated Version',
                'version' => '999.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project version not found',
            ]);
    }

    #[Test]
    public function test_update_version_successfully_with_new_files()
    {
        Storage::fake('projects');

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

        // Create existing file
        $existingFile = ProjectFile::factory()->create([
            'project_version_id' => $version->id,
            'name' => 'existing-file.zip',
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        $newFile = UploadedFile::fake()->create('new-file.zip', 50);

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), [
                'name' => 'Version 1.0.1',
                'version' => '1.0.1',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'files' => [$newFile],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Version updated successfully',
            ]);

        // Verify version was updated
        $this->assertDatabaseHas('project_version', [
            'id' => $version->id,
            'version' => '1.0.1',
            'name' => 'Version 1.0.1',
        ]);

        // Verify new file was added
        $this->assertDatabaseHas('project_file', [
            'project_version_id' => $version->id,
            'name' => 'new-file.zip',
        ]);

        // Existing file should still exist
        $this->assertDatabaseHas('project_file', [
            'id' => $existingFile->id,
            'name' => 'existing-file.zip',
        ]);
    }

    #[Test]
    public function test_update_version_removing_specific_files()
    {
        Storage::fake('projects');

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

        // Create existing files
        $file1 = ProjectFile::factory()->create([
            'project_version_id' => $version->id,
            'name' => 'file1.zip',
        ]);
        $file2 = ProjectFile::factory()->create([
            'project_version_id' => $version->id,
            'name' => 'file2.zip',
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'files_to_remove' => ['file1.zip'],
            ]);

        $response->assertStatus(200);

        // Verify file1 was removed
        $this->assertDatabaseMissing('project_file', [
            'id' => $file1->id,
        ]);

        // Verify file2 still exists
        $this->assertDatabaseHas('project_file', [
            'id' => $file2->id,
        ]);
    }

    #[Test]
    public function test_update_version_clearing_all_files()
    {
        Storage::fake('projects');

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

        // Create existing files
        $file1 = ProjectFile::factory()->create([
            'project_version_id' => $version->id,
            'name' => 'file1.zip',
        ]);
        $file2 = ProjectFile::factory()->create([
            'project_version_id' => $version->id,
            'name' => 'file2.zip',
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        $newFile = UploadedFile::fake()->create('new-file.zip', 50);

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'clean_existing_files' => true,
                'files' => [$newFile],
            ]);

        $response->assertStatus(200);

        // Verify old files were removed
        $this->assertDatabaseMissing('project_file', ['id' => $file1->id]);
        $this->assertDatabaseMissing('project_file', ['id' => $file2->id]);

        // Verify new file was added
        $this->assertDatabaseHas('project_file', [
            'project_version_id' => $version->id,
            'name' => 'new-file.zip',
        ]);

        $version->refresh();
        $this->assertCount(1, $version->files);
    }

    #[Test]
    public function test_update_version_data_successfully()
    {
        Storage::fake('projects');

        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(5),
            'release_type' => 'beta',
            'changelog' => 'Original changelog',
        ]);

        ProjectFile::factory()->create([
            'project_version_id' => $version->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $newReleaseDate = now()->format('Y-m-d');
        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), [
                'name' => 'Version 1.0.0 Final',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => $newReleaseDate,
                'changelog' => 'Updated changelog',
            ]);

        $response->assertStatus(200);

        $version->refresh();
        $this->assertEquals('Version 1.0.0 Final', $version->name);
        $this->assertEquals('release', $version->release_type->value);
        $this->assertEquals('Updated changelog', $version->changelog);
        $this->assertEquals($newReleaseDate, $version->release_date->format('Y-m-d'));
    }

    #[Test]
    public function test_update_version_tags()
    {
        Storage::fake('projects');

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

        ProjectFile::factory()->create([
            'project_version_id' => $version->id,
        ]);

        $oldTag = ProjectVersionTag::factory()->create();
        $oldTag->projectTypes()->attach($this->projectType);
        $version->tags()->attach($oldTag);

        $newTag = ProjectVersionTag::factory()->create();
        $newTag->projectTypes()->attach($this->projectType);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'tags' => [$newTag->slug],
            ]);

        $response->assertStatus(200);

        $version->refresh();
        $this->assertFalse($version->tags->contains($oldTag));
        $this->assertTrue($version->tags->contains($newTag));
    }

    #[Test]
    public function test_update_version_dependencies()
    {
        Storage::fake('projects');

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

        ProjectFile::factory()->create([
            'project_version_id' => $version->id,
        ]);

        // Create an existing dependency that will be replaced
        $oldDependencyProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $oldDependencyVersion = $oldDependencyProject->versions()->create([
            'name' => 'Old Dependency',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        ProjectVersionDependency::factory()->specificVersion()->create([
            'project_version_id' => $version->id,
            'dependency_project_version_id' => $oldDependencyVersion->id,
            'dependency_type' => 'required',
        ]);

        // Create a new dependency project to add
        $newDependencyProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $newDependencyVersion = $newDependencyProject->versions()->create([
            'name' => 'New Dependency',
            'version' => '2.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        // Update with new dependencies - old one should be removed, new one added
        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'dependencies' => [
                    [
                        'project' => $newDependencyProject->slug,
                        'version' => $newDependencyVersion->version,
                        'type' => 'optional',
                        'external' => false,
                    ],
                ],
            ]);

        $response->assertStatus(200);

        $version->refresh();

        // Should have exactly 1 dependency now
        $this->assertCount(1, $version->dependencies);

        // The new dependency should exist
        $newDep = $version->dependencies->first();
        $this->assertEquals('optional', $newDep->dependency_type->value);
        $this->assertEquals($newDependencyVersion->id, $newDep->dependency_project_version_id);
    }

    #[Test]
    public function test_update_version_add_dependency()
    {
        Storage::fake('projects');

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

        ProjectFile::factory()->create([
            'project_version_id' => $version->id,
        ]);

        // Create a new dependency project to add
        $dependencyProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $dependencyVersion = $dependencyProject->versions()->create([
            'name' => 'New Dependency',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        // Add a new dependency
        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'dependencies' => [
                    [
                        'project' => $dependencyProject->slug,
                        'version' => $dependencyVersion->version,
                        'type' => 'required',
                        'external' => false,
                    ],
                ],
            ]);

        $response->assertStatus(200);

        $version->refresh();
        $this->assertCount(1, $version->dependencies);

        $dependency = $version->dependencies->first();
        $this->assertEquals('required', $dependency->dependency_type->value);
        $this->assertEquals($dependencyVersion->id, $dependency->dependency_project_version_id);
    }

    #[Test]
    public function test_update_version_modify_dependency()
    {
        Storage::fake('projects');

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

        ProjectFile::factory()->create([
            'project_version_id' => $version->id,
        ]);

        // Create an existing dependency
        $dependencyProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $dependencyVersion = $dependencyProject->versions()->create([
            'name' => 'Dependency',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $existingDependency = ProjectVersionDependency::factory()->specificVersion()->create([
            'project_version_id' => $version->id,
            'dependency_project_version_id' => $dependencyVersion->id,
            'dependency_type' => 'required',
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        // Update the dependency type
        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'dependencies' => [
                    [
                        'project' => $dependencyProject->slug,
                        'version' => $dependencyVersion->version,
                        'type' => 'optional',
                        'external' => false,
                    ],
                ],
            ]);

        $response->assertStatus(200);

        $version->refresh();
        $this->assertCount(1, $version->dependencies);

        $dependency = $version->dependencies->first();
        $this->assertEquals('optional', $dependency->dependency_type->value);
    }

    #[Test]
    public function test_update_version_remove_dependency()
    {
        Storage::fake('projects');

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

        ProjectFile::factory()->create([
            'project_version_id' => $version->id,
        ]);

        // Create an existing dependency
        $dependencyProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $dependencyVersion = $dependencyProject->versions()->create([
            'name' => 'Dependency',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $existingDependency = ProjectVersionDependency::factory()->specificVersion()->create([
            'project_version_id' => $version->id,
            'dependency_project_version_id' => $dependencyVersion->id,
            'dependency_type' => 'required',
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        // Update with empty dependencies array to remove all
        $response = $this->withToken($token)
            ->postJson(route('api.v1.project_version.update', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]), [
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_type' => 'release',
                'release_date' => now()->format('Y-m-d'),
                'dependencies' => [],
            ]);

        $response->assertStatus(200);

        $version->refresh();
        $this->assertCount(0, $version->dependencies);

        // The dependency should be removed from the database
        $this->assertDatabaseMissing('project_version_dependency', [
            'id' => $existingDependency->id,
        ]);
    }

    // =====================================================
    // DELETE /api/v1/project/{slug}/version/{version} - Destroy Tests
    // =====================================================

    #[Test]
    public function test_destroy_version_without_token_returns_unauthorized()
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

        $response = $this->deleteJson(route('api.v1.project_version.destroy', [
            'slug' => $project->slug,
            'version' => $version->version,
        ]));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_destroy_version_with_invalid_token_returns_unauthorized()
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

        $response = $this->withToken('invalid-token-12345')
            ->deleteJson(route('api.v1.project_version.destroy', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_destroy_version_with_expired_token_returns_unauthorized()
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

        $token = $user->createToken('test-token', ['*'], now()->subDay())->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson(route('api.v1.project_version.destroy', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_destroy_version_by_non_owner_returns_forbidden()
    {
        $owner = User::factory()->create();
        $project = Project::factory()->owner($owner)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $version = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $otherUser = User::factory()->create();
        $token = $otherUser->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson(route('api.v1.project_version.destroy', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]));

        $response->assertStatus(403);
    }

    #[Test]
    public function test_destroy_version_successfully()
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

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson(route('api.v1.project_version.destroy', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('project_version', ['id' => $version->id]);
    }

    #[Test]
    public function test_destroy_version_with_nonexistent_project_returns_not_found()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson(route('api.v1.project_version.destroy', [
                'slug' => 'nonexistent-project',
                'version' => '1.0.0',
            ]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project not found',
            ]);
    }

    #[Test]
    public function test_destroy_version_with_nonexistent_version_returns_not_found()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson(route('api.v1.project_version.destroy', [
                'slug' => $project->slug,
                'version' => '999.0.0',
            ]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project version not found',
            ]);
    }

    #[Test]
    public function test_destroy_version_deletes_associated_files_from_storage()
    {
        Storage::fake('projects');

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

        // Create files
        $file1 = ProjectFile::factory()->create([
            'project_version_id' => $version->id,
            'name' => 'file1.zip',
        ]);
        $file2 = ProjectFile::factory()->create([
            'project_version_id' => $version->id,
            'name' => 'file2.zip',
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson(route('api.v1.project_version.destroy', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]));

        $response->assertStatus(204);

        // Verify files were deleted from database
        $this->assertDatabaseMissing('project_file', ['id' => $file1->id]);
        $this->assertDatabaseMissing('project_file', ['id' => $file2->id]);
    }

    #[Test]
    public function test_destroy_version_removes_dependencies()
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

        // Create dependency
        ProjectVersionDependency::factory()->specificVersion()->create([
            'project_version_id' => $version->id,
            'dependency_project_version_id' => $dependencyVersion->id,
            'dependency_type' => 'required',
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson(route('api.v1.project_version.destroy', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]));

        $response->assertStatus(204);

        // Verify dependencies were removed
        $this->assertDatabaseMissing('project_version_dependency', [
            'project_version_id' => $version->id,
        ]);
    }

    #[Test]
    public function test_destroy_version_removes_tag_relationships()
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

        $tag = ProjectVersionTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);
        $version->tags()->attach($tag);

        $this->assertDatabaseHas('project_version_project_version_tag', [
            'project_version_id' => $version->id,
            'tag_id' => $tag->id,
        ]);

        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson(route('api.v1.project_version.destroy', [
                'slug' => $project->slug,
                'version' => $version->version,
            ]));

        $response->assertStatus(204);

        // Verify tag relationship was removed
        $this->assertDatabaseMissing('project_version_project_version_tag', [
            'project_version_id' => $version->id,
        ]);
    }
}
