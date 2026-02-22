<?php

namespace Tests\Feature\Api;

use App\Models\ProjectTag;
use App\Models\ProjectTagGroup;
use App\Models\ProjectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectTagTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectType = ProjectType::factory()->create();
    }

    #[Test]
    public function test_get_project_tags_returns_hierarchical_structure_by_default()
    {
        $mainTag = ProjectTag::factory()->create();
        $mainTag->projectTypes()->attach($this->projectType);

        $subTag1 = ProjectTag::factory()->create(['parent_id' => $mainTag->id]);
        $subTag1->projectTypes()->attach($this->projectType);

        $subTag2 = ProjectTag::factory()->create(['parent_id' => $mainTag->id]);
        $subTag2->projectTypes()->attach($this->projectType);

        $response = $this->getJson('/api/v1/project_tags');

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
    public function test_get_project_tags_with_plain_parameter_returns_flat_list()
    {
        ProjectTag::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/project_tags?plain=true');

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
    public function test_get_project_tags_returns_empty_array_when_none_exist()
    {
        $response = $this->getJson('/api/v1/project_tags');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    #[Test]
    public function test_get_project_tag_by_valid_slug()
    {
        $tagGroup = ProjectTagGroup::factory()->create([
            'name' => 'Category',
            'slug' => 'category',
        ]);

        $tag = ProjectTag::factory()->create([
            'name' => 'Gameplay',
            'slug' => 'gameplay',
            'icon' => 'fa-gamepad',
            'project_tag_group_id' => $tagGroup->id,
        ]);
        $tag->projectTypes()->attach($this->projectType);

        $response = $this->getJson('/api/v1/project_tag/gameplay');

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
                    'name' => 'Gameplay',
                    'slug' => 'gameplay',
                    'icon' => 'fa-gamepad',
                    'tag_group' => 'category',
                ],
            ]);
    }

    #[Test]
    public function test_get_project_tag_by_invalid_slug_returns_404()
    {
        $response = $this->getJson('/api/v1/project_tag/nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project tag not found',
            ]);
    }

    #[Test]
    public function test_project_tag_response_includes_relationships()
    {
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        $response = $this->getJson('/api/v1/project_tag/' . $tag->slug);

        $data = $response->json('data');

        $this->assertArrayHasKey('main_tag', $data);
        $this->assertArrayHasKey('project_types', $data);
        $this->assertIsArray($data['project_types']);
    }

    #[Test]
    public function test_project_tag_response_has_required_fields()
    {
        $tag = ProjectTag::factory()->create();

        $response = $this->getJson('/api/v1/project_tag/' . $tag->slug);

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
    public function test_only_main_tags_returned_without_plain_parameter()
    {
        $mainTag = ProjectTag::factory()->create();
        $mainTag->projectTypes()->attach($this->projectType);

        $subTag = ProjectTag::factory()->create(['parent_id' => $mainTag->id]);
        $subTag->projectTypes()->attach($this->projectType);

        $response = $this->getJson('/api/v1/project_tags');

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals($mainTag->slug, $data[0]['slug']);
    }

    #[Test]
    public function test_project_tags_are_ordered_by_priority_then_slug_and_do_not_expose_display_priority()
    {
        $mainAlpha = ProjectTag::factory()->create([
            'name' => 'Main Alpha',
            'slug' => 'main-alpha',
            'display_priority' => 10,
        ]);
        $mainAlpha->projectTypes()->attach($this->projectType);

        $mainBeta = ProjectTag::factory()->create([
            'name' => 'Main Beta',
            'slug' => 'main-beta',
            'display_priority' => 10,
        ]);
        $mainBeta->projectTypes()->attach($this->projectType);

        $mainLow = ProjectTag::factory()->create([
            'name' => 'Main Low',
            'slug' => 'main-low',
            'display_priority' => 1,
        ]);
        $mainLow->projectTypes()->attach($this->projectType);

        $subB = ProjectTag::factory()->create([
            'name' => 'Sub B',
            'slug' => 'sub-b',
            'display_priority' => 7,
            'parent_id' => $mainAlpha->id,
        ]);
        $subB->projectTypes()->attach($this->projectType);

        $subA = ProjectTag::factory()->create([
            'name' => 'Sub A',
            'slug' => 'sub-a',
            'display_priority' => 7,
            'parent_id' => $mainAlpha->id,
        ]);
        $subA->projectTypes()->attach($this->projectType);

        $response = $this->getJson('/api/v1/project_tags');
        $response->assertOk();

        $data = $response->json('data');

        $this->assertSame(['main-alpha', 'main-beta', 'main-low'], array_column($data, 'slug'));
        $this->assertSame(['sub-a', 'sub-b'], array_column($data[0]['sub_tags'], 'slug'));

        foreach ($data as $tag) {
            $this->assertArrayNotHasKey('display_priority', $tag);

            foreach ($tag['sub_tags'] ?? [] as $subTag) {
                $this->assertArrayNotHasKey('display_priority', $subTag);
            }
        }
    }
}
