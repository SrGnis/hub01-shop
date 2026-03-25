<?php

namespace Tests\Unit;

use App\Enums\CollectionSystemType;
use App\Enums\CollectionVisibility;
use App\Models\Collection;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use App\Services\CollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FavoriteServiceTest extends TestCase
{
    use RefreshDatabase;

    private CollectionService $collectionService;

    private ProjectType $projectType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collectionService = $this->app->make(CollectionService::class);
        $this->projectType = ProjectType::factory()->create();
    }

    #[Test]
    public function test_toggle_favorite_adds_then_removes_entry(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $added = $this->collectionService->toggleFavorite($user, $project);

        $this->assertTrue($added['favorited']);
        $this->assertTrue($this->collectionService->isFavorited($user, $project));
        $this->assertDatabaseHas('collection_entry', [
            'collection_uid' => $added['collection']->uid,
            'project_id' => $project->id,
            'sort_order' => 0,
        ]);

        $removed = $this->collectionService->toggleFavorite($user, $project);

        $this->assertFalse($removed['favorited']);
        $this->assertFalse($this->collectionService->isFavorited($user, $project));
        $this->assertDatabaseMissing('collection_entry', [
            'collection_uid' => $removed['collection']->uid,
            'project_id' => $project->id,
        ]);
    }

    #[Test]
    public function test_get_or_create_favorites_collection_enforces_single_private_non_shareable_collection(): void
    {
        $user = User::factory()->create();

        $favorites = Collection::query()->create([
            'user_id' => $user->id,
            'name' => 'My Favs',
            'visibility' => CollectionVisibility::HIDDEN,
            'system_type' => CollectionSystemType::FAVORITES,
            'hidden_share_token' => 'SHOULD-BE-REMOVED',
        ]);

        $resolved = $this->collectionService->getOrCreateFavoritesCollection($user);

        $this->assertEquals($favorites->uid, $resolved->uid);
        $this->assertEquals(CollectionVisibility::PRIVATE, $resolved->visibility);
        $this->assertNull($resolved->hidden_share_token);
        $this->assertEquals(1, Collection::query()
            ->where('user_id', $user->id)
            ->where('system_type', CollectionSystemType::FAVORITES)
            ->count());
    }

    #[Test]
    public function test_toggle_favorite_normalizes_sort_order_after_removal(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();

        $projectA = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);
        $projectB = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);
        $projectC = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);

        $favorites = $this->collectionService->getOrCreateFavoritesCollection($user);

        $favorites->entries()->create(['project_id' => $projectA->id, 'sort_order' => 0]);
        $favorites->entries()->create(['project_id' => $projectB->id, 'sort_order' => 1]);
        $favorites->entries()->create(['project_id' => $projectC->id, 'sort_order' => 2]);

        $result = $this->collectionService->toggleFavorite($user, $projectB);

        $this->assertFalse($result['favorited']);
        $this->assertDatabaseHas('collection_entry', [
            'collection_uid' => $favorites->uid,
            'project_id' => $projectA->id,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('collection_entry', [
            'collection_uid' => $favorites->uid,
            'project_id' => $projectC->id,
            'sort_order' => 1,
        ]);
    }
}
