<?php

namespace Tests\Feature\Collection\Livewire;

use App\Enums\CollectionVisibility;
use App\Models\Collection;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectionPagesTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectType = ProjectType::factory()->create();
    }

    #[Test]
    public function test_collection_show_renders_unavailable_project_fallback_for_deleted_entry_project(): void
    {
        $owner = User::factory()->create();

        $collection = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Public Collection',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        $project = Project::factory()->owner($owner)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $collection->entries()->create([
            'project_id' => $project->id,
            'note' => 'Will be unavailable',
            'sort_order' => 0,
        ]);

        $project->delete();

        $this->get(route('collection.show', $collection))
            ->assertOk()
            ->assertSee('Unavailable project')
            ->assertSee('This project was deleted or is no longer available.')
            ->assertSee('Will be unavailable');
    }

    #[Test]
    public function test_collection_edit_renders_unavailable_project_fallback_for_deleted_entry_project(): void
    {
        $owner = User::factory()->create();

        $collection = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private Collection',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $project = Project::factory()->owner($owner)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $collection->entries()->create([
            'project_id' => $project->id,
            'note' => 'Edit fallback note',
            'sort_order' => 0,
        ]);

        $project->delete();

        $this->actingAs($owner)
            ->get(route('collection.edit', $collection))
            ->assertOk()
            ->assertSee('Unavailable project')
            ->assertSee('This project was deleted or is no longer available.')
            ->assertSee('Edit fallback note');
    }
}

