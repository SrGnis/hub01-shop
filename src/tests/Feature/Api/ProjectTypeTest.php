<?php

namespace Tests\Feature\Api;

use App\Models\ProjectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_get_project_types_returns_all_types()
    {
        ProjectType::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/project_types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'slug',
                        'icon',
                    ],
                ],
            ]);

        // 3 default project types from migration + 3 created by factory = 6 total
        $this->assertCount(6, $response->json('data'));
    }

    #[Test]
    public function test_get_project_types_returns_empty_array_when_none_exist()
    {
        $response = $this->getJson('/api/v1/project_types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ])
            ->assertJson([
                'data' => [],
            ]);
    }

    #[Test]
    public function test_get_project_type_by_valid_slug()
    {
        $projectType = ProjectType::factory()->create([
            'display_name' => 'Custom Type',
            'value' => 'custom_type',
            'icon' => 'fa-cube',
        ]);

        $response = $this->getJson('/api/v1/project_type/custom_type');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'slug',
                    'icon',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Custom Type',
                    'slug' => 'custom_type',
                    'icon' => 'fa-cube',
                ],
            ]);
    }

    #[Test]
    public function test_get_project_type_by_invalid_slug_returns_404()
    {
        $response = $this->getJson('/api/v1/project_type/nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Project type not found',
            ]);
    }

    #[Test]
    public function test_project_type_response_has_required_fields()
    {
        $projectType = ProjectType::factory()->create();

        $response = $this->getJson('/api/v1/project_type/' . $projectType->value);

        $data = $response->json('data');

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('icon', $data);
        $this->assertIsString($data['name']);
        $this->assertIsString($data['slug']);
        $this->assertIsString($data['icon']);
    }

    #[Test]
    public function test_project_type_collection_response_has_required_fields()
    {
        ProjectType::factory()->create();

        $response = $this->getJson('/api/v1/project_types');

        $data = $response->json('data.0');

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('icon', $data);
        $this->assertIsString($data['name']);
        $this->assertIsString($data['slug']);
        $this->assertIsString($data['icon']);
    }
}
