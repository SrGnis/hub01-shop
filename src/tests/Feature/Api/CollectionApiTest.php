<?php

namespace Tests\Feature\Api;

use App\Enums\CollectionSystemType;
use App\Enums\CollectionVisibility;
use App\Models\Collection;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectionApiTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectType = ProjectType::factory()->create();
    }

    #[Test]
    public function test_public_collections_search_returns_only_public_collections(): void
    {
        $owner = User::factory()->create();

        $public = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Public Picks',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private Picks',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Hidden Picks',
            'visibility' => CollectionVisibility::HIDDEN,
            'hidden_share_token' => 'HIDDEN-TOKEN-1',
        ]);

        Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PUBLIC,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        $response = $this->getJson(route('api.v1.collections'));

        $response->assertStatus(200);
        $uids = collect($response->json('data'))->pluck('uid')->all();

        $this->assertContains($public->uid, $uids);
        $this->assertCount(1, $uids);
    }

    #[Test]
    public function test_public_collection_detail_returns_not_found_for_private_collection(): void
    {
        $owner = User::factory()->create();
        $private = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Private Picks',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $response = $this->getJson(route('api.v1.collection', ['uid' => $private->uid]));

        $response->assertStatus(404);
    }

    #[Test]
    public function test_hidden_collection_detail_requires_valid_token(): void
    {
        $owner = User::factory()->create();

        $hidden = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Hidden Picks',
            'visibility' => CollectionVisibility::HIDDEN,
            'hidden_share_token' => 'SHARE123TOKEN',
        ]);

        $validResponse = $this->getJson(route('api.v1.collection.hidden', ['token' => $hidden->hidden_share_token]));
        $validResponse->assertStatus(200)
            ->assertJsonPath('data.uid', $hidden->uid);

        $invalidResponse = $this->getJson(route('api.v1.collection.hidden', ['token' => 'WRONG-TOKEN']));
        $invalidResponse->assertStatus(404);
    }

    #[Test]
    public function test_hidden_collection_detail_denies_missing_token_route(): void
    {
        $this->getJson('/api/v1/collection/hidden')
            ->assertStatus(404);
    }

    #[Test]
    public function test_collection_visibility_matrix_for_guest_non_owner_and_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();

        $public = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Public',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        $private = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Private',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $hidden = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Hidden',
            'visibility' => CollectionVisibility::HIDDEN,
            'hidden_share_token' => 'VISIBILITY-HIDDEN-TOKEN',
        ]);

        // Guest visibility
        $this->getJson(route('api.v1.collection', ['uid' => $public->uid]))->assertStatus(200);
        $this->getJson(route('api.v1.collection', ['uid' => $private->uid]))->assertStatus(404);
        $this->getJson(route('api.v1.collection', ['uid' => $hidden->uid]))->assertStatus(404);
        $this->getJson(route('api.v1.collection.hidden', ['token' => $hidden->hidden_share_token]))->assertStatus(200);

        // Non-owner visibility
        $this->actingAs($nonOwner)->getJson(route('api.v1.collection', ['uid' => $public->uid]))->assertStatus(200);
        $this->actingAs($nonOwner)->getJson(route('api.v1.collection', ['uid' => $private->uid]))->assertStatus(404);
        $this->actingAs($nonOwner)->getJson(route('api.v1.collection', ['uid' => $hidden->uid]))->assertStatus(404);
        $this->actingAs($nonOwner)->getJson(route('api.v1.collection.hidden', ['token' => $hidden->hidden_share_token]))->assertStatus(200);

        // Owner visibility via owner endpoints
        $this->actingAs($owner)->getJson(route('api.v1.me.collection', ['uid' => $public->uid]))->assertStatus(200);
        $this->actingAs($owner)->getJson(route('api.v1.me.collection', ['uid' => $private->uid]))->assertStatus(200);
        $this->actingAs($owner)->getJson(route('api.v1.me.collection', ['uid' => $hidden->uid]))->assertStatus(200);

        // Non-owner denied on owner endpoint
        $this->actingAs($nonOwner)->getJson(route('api.v1.me.collection', ['uid' => $private->uid]))->assertStatus(404);
    }

    #[Test]
    public function test_owner_collections_search_returns_owner_public_private_and_hidden(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Public',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);
        Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Private',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);
        Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Hidden',
            'visibility' => CollectionVisibility::HIDDEN,
            'hidden_share_token' => 'OWN-HIDDEN-TOKEN',
        ]);
        Collection::query()->create([
            'user_id' => $other->id,
            'name' => 'Other Public',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        $response = $this->actingAs($owner)->getJson(route('api.v1.me.collections'));

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function test_owner_can_create_update_and_delete_non_system_collection(): void
    {
        $owner = User::factory()->create();

        $createResponse = $this->actingAs($owner)
            ->postJson(route('api.v1.me.collections.store'), [
                'name' => 'My Collection',
                'description' => 'My Description',
                'visibility' => 'public',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'My Collection')
            ->assertJsonPath('data.visibility', 'public');

        $uid = $createResponse->json('data.uid');

        $updateResponse = $this->actingAs($owner)
            ->patchJson(route('api.v1.me.collection.update', ['uid' => $uid]), [
                'name' => 'My Updated Collection',
                'visibility' => 'hidden',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'My Updated Collection')
            ->assertJsonPath('data.visibility', 'hidden')
            ->assertJsonStructure(['data' => ['hidden_share_token']]);

        $deleteResponse = $this->actingAs($owner)
            ->deleteJson(route('api.v1.me.collection.destroy', ['uid' => $uid]));

        $deleteResponse->assertStatus(204);
    }

    #[Test]
    public function test_owner_cannot_delete_favorites_system_collection(): void
    {
        $owner = User::factory()->create();

        $favorites = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PRIVATE,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        $response = $this->actingAs($owner)
            ->deleteJson(route('api.v1.me.collection.destroy', ['uid' => $favorites->uid]));

        $response->assertStatus(403);
    }

    #[Test]
    public function test_owner_cannot_make_favorites_collection_shareable_via_update(): void
    {
        $owner = User::factory()->create();

        $favorites = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PRIVATE,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        $response = $this->actingAs($owner)
            ->patchJson(route('api.v1.me.collection.update', ['uid' => $favorites->uid]), [
                'visibility' => 'hidden',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.hidden_share_token', null);
    }

    #[Test]
    public function test_owner_can_add_update_note_reorder_and_remove_entries(): void
    {
        $owner = User::factory()->create();

        $collection = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Entry Test',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $projectA = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);
        $projectB = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);

        $addA = $this->actingAs($owner)->postJson(route('api.v1.me.collection.entries.store', ['uid' => $collection->uid]), [
            'project' => $projectA->slug,
            'note' => 'Note A',
        ]);
        $addA->assertStatus(201);

        $addB = $this->actingAs($owner)->postJson(route('api.v1.me.collection.entries.store', ['uid' => $collection->uid]), [
            'project' => $projectB->slug,
            'note' => 'Note B',
        ]);
        $addB->assertStatus(201);

        $entries = collect($addB->json('data.entries'));
        $entryA = $entries->firstWhere('project.slug', $projectA->slug);
        $entryB = $entries->firstWhere('project.slug', $projectB->slug);

        $noteResponse = $this->actingAs($owner)->patchJson(route('api.v1.me.collection.entry.note.update', [
            'uid' => $collection->uid,
            'entryUid' => $entryA['uid'],
        ]), [
            'note' => 'Updated Note A',
        ]);
        $noteResponse->assertStatus(200);

        $reorderResponse = $this->actingAs($owner)->postJson(route('api.v1.me.collection.entries.reorder', [
            'uid' => $collection->uid,
        ]), [
            'entry_uids' => [$entryB['uid'], $entryA['uid']],
        ]);
        $reorderResponse->assertStatus(200)
            ->assertJsonPath('data.entries.0.uid', $entryB['uid']);

        $removeResponse = $this->actingAs($owner)->deleteJson(route('api.v1.me.collection.entry.destroy', [
            'uid' => $collection->uid,
            'entryUid' => $entryA['uid'],
        ]));
        $removeResponse->assertStatus(204);
    }

    #[Test]
    public function test_entry_note_update_is_scoped_to_collection(): void
    {
        $owner = User::factory()->create();

        $collectionA = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Collection A',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $collectionB = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Collection B',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $projectA = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);
        $projectB = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);

        $entryA = $collectionA->entries()->create([
            'project_id' => $projectA->id,
            'note' => 'A Note',
            'sort_order' => 0,
        ]);

        $entryB = $collectionB->entries()->create([
            'project_id' => $projectB->id,
            'note' => 'B Note',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($owner)->patchJson(route('api.v1.me.collection.entry.note.update', [
            'uid' => $collectionA->uid,
            'entryUid' => $entryB->uid,
        ]), [
            'note' => 'Updated wrong entry',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Entry does not belong to the provided collection.');

        $this->assertEquals('A Note', $entryA->fresh()->note);
        $this->assertEquals('B Note', $entryB->fresh()->note);
    }

    #[Test]
    public function test_reorder_entries_persists_sort_order_and_returns_stable_order(): void
    {
        $owner = User::factory()->create();

        $collection = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Persisted Reorder',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $projectA = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);
        $projectB = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);
        $projectC = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);

        $entryA = $collection->entries()->create(['project_id' => $projectA->id, 'sort_order' => 0]);
        $entryB = $collection->entries()->create(['project_id' => $projectB->id, 'sort_order' => 1]);
        $entryC = $collection->entries()->create(['project_id' => $projectC->id, 'sort_order' => 2]);

        $orderedUids = [$entryC->uid, $entryA->uid, $entryB->uid];

        $reorderResponse = $this->actingAs($owner)->postJson(route('api.v1.me.collection.entries.reorder', [
            'uid' => $collection->uid,
        ]), [
            'entry_uids' => $orderedUids,
        ]);

        $reorderResponse->assertStatus(200)
            ->assertJsonPath('data.entries.0.uid', $entryC->uid)
            ->assertJsonPath('data.entries.1.uid', $entryA->uid)
            ->assertJsonPath('data.entries.2.uid', $entryB->uid);

        $this->assertDatabaseHas('collection_entry', ['uid' => $entryC->uid, 'sort_order' => 0]);
        $this->assertDatabaseHas('collection_entry', ['uid' => $entryA->uid, 'sort_order' => 1]);
        $this->assertDatabaseHas('collection_entry', ['uid' => $entryB->uid, 'sort_order' => 2]);

        $ownerShowResponse = $this->actingAs($owner)
            ->getJson(route('api.v1.me.collection', ['uid' => $collection->uid]));

        $ownerShowResponse->assertStatus(200)
            ->assertJsonPath('data.entries.0.uid', $entryC->uid)
            ->assertJsonPath('data.entries.1.uid', $entryA->uid)
            ->assertJsonPath('data.entries.2.uid', $entryB->uid);
    }

    #[Test]
    public function test_owner_quick_create_private_collection_and_attach_project(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);

        $response = $this->actingAs($owner)
            ->postJson(route('api.v1.me.collections.quick_create'), [
                'name' => 'Quick Picks',
                'description' => 'Quick list',
                'project' => $project->slug,
                'note' => 'Great project',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Quick Picks')
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonCount(1, 'data.entries')
            ->assertJsonPath('data.entries.0.project.slug', $project->slug)
            ->assertJsonPath('data.entries.0.note', 'Great project');
    }

    #[Test]
    public function test_collection_show_returns_null_project_payload_for_deleted_project_entries(): void
    {
        $owner = User::factory()->create();

        $collection = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Public with deleted project',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        $project = Project::factory()->owner($owner)->create(['project_type_id' => $this->projectType->id]);

        $collection->entries()->create([
            'project_id' => $project->id,
            'note' => 'Will become unavailable',
            'sort_order' => 0,
        ]);

        $project->delete();

        $response = $this->getJson(route('api.v1.collection', ['uid' => $collection->uid]));

        $response->assertStatus(200)
            ->assertJsonPath('data.entries.0.note', 'Will become unavailable')
            ->assertJsonPath('data.entries.0.project', null);
    }
}
