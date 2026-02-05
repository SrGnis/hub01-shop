<?php

namespace Tests\Feature\Api;

use App\Models\ProjectType;
use App\Models\ProjectVersionTag;
use App\Models\ProjectVersionTagGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VersionTagTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectType = ProjectType::factory()->create();
    }

    #[Test]
    public function test_get_version_tags_returns_hierarchical_structure_by_default()
    {
        $mainTag = ProjectVersionTag::factory()->create();
        $mainTag->projectTypes()->attach($this->projectType);

        $subTag1 = ProjectVersionTag::factory()->create(['parent_id' => $mainTag->id]);
        $subTag1->projectTypes()->attach($this->projectType);

        $subTag2 = ProjectVersionTag::factory()->create(['parent_id' => $mainTag->id]);
        $subTag2->projectTypes()->attach($this->projectType);

        $response = $this->getJson('/api/v1/version_tags');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'slug',
                        'icon',
                        'tag_group',
                        'project_types',
                        'main_tag',
                        'sub_tags' => [
                            '*' => [
                                'name',
                                'slug',
                                'icon',
                                'tag_group',
                                'project_types',
                                'main_tag',
                            ],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertCount(2, $data[0]['sub_tags']);
    }

    #[Test]
    public function test_get_version_tags_with_plain_parameter_returns_flat_list()
    {
        ProjectVersionTag::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/version_tags?plain=true');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'slug',
                        'icon',
                        'tag_group',
                        'project_types',
                        'main_tag',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertArrayNotHasKey('sub_tags', $data[0]);
    }

    #[Test]
    public function test_get_version_tags_returns_empty_array_when_none_exist()
    {
        $response = $this->getJson('/api/v1/version_tags');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    #[Test]
    public function test_get_version_tag_by_valid_slug()
    {
        $tagGroup = ProjectVersionTagGroup::factory()->create([
            'name' => 'Category',
            'slug' => 'category',
        ]);

        $tag = ProjectVersionTag::factory()->create([
            'name' => 'Stable',
            'slug' => 'stable',
            'icon' => 'fa-check',
            'project_version_tag_group_id' => $tagGroup->id,
        ]);
        $tag->projectTypes()->attach($this->projectType);

        $response = $this->getJson('/api/v1/version_tag/stable');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'slug',
                    'icon',
                    'tag_group',
                    'project_types',
                    'main_tag',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Stable',
                    'slug' => 'stable',
                    'icon' => 'fa-check',
                    'tag_group' => $tagGroup->slug,
                ],
            ]);
    }

    #[Test]
    public function test_get_version_tag_by_invalid_slug_returns_404()
    {
        $response = $this->getJson('/api/v1/version_tag/nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project version tag not found',
            ]);
    }

    #[Test]
    public function test_version_tag_response_includes_relationships()
    {
        $tag = ProjectVersionTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        $response = $this->getJson('/api/v1/version_tag/' . $tag->slug);

        $data = $response->json('data');

        $this->assertArrayHasKey('main_tag', $data);
        $this->assertArrayHasKey('project_types', $data);
        $this->assertIsArray($data['project_types']);
    }

    #[Test]
    public function test_version_tag_response_has_required_fields()
    {
        $tag = ProjectVersionTag::factory()->create();

        $response = $this->getJson('/api/v1/version_tag/' . $tag->slug);

        $data = $response->json('data');

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('icon', $data);
        $this->assertArrayHasKey('tag_group', $data);
        $this->assertArrayHasKey('project_types', $data);
        $this->assertArrayHasKey('main_tag', $data);
        $this->assertIsString($data['name']);
        $this->assertIsString($data['slug']);
        $this->assertIsString($data['icon']);
        // tag_group can be null if no tag group is assigned
        $this->assertTrue(is_string($data['tag_group']) || is_null($data['tag_group']));
    }

    #[Test]
    public function test_only_main_version_tags_returned_without_plain_parameter()
    {
        $mainTag = ProjectVersionTag::factory()->create();
        $mainTag->projectTypes()->attach($this->projectType);

        $subTag = ProjectVersionTag::factory()->create(['parent_id' => $mainTag->id]);
        $subTag->projectTypes()->attach($this->projectType);

        $response = $this->getJson('/api/v1/version_tags');

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals($mainTag->slug, $data[0]['slug']);
    }
}
