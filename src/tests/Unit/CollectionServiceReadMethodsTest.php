<?php

namespace Tests\Unit;

use App\Enums\CollectionSystemType;
use App\Enums\CollectionVisibility;
use App\Models\Collection;
use App\Models\CollectionEntry;
use App\Models\Project;
use App\Models\User;
use App\Services\CollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectionServiceReadMethodsTest extends TestCase
{
    use RefreshDatabase;

    private CollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CollectionService();
    }

    #[Test]
    public function paginate_public_returns_only_public_non_favorites_collections(): void
    {
        $owner = User::factory()->create();

        $public = Collection::create([
            'user_id' => $owner->id,
            'name' => 'Public Picks',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        Collection::create([
            'user_id' => $owner->id,
            'name' => 'Private Picks',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        Collection::create([
            'user_id' => $owner->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PUBLIC,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        $result = $this->service->paginatePublic();

        $this->assertCount(1, $result->items());
        $this->assertSame($public->uid, $result->items()[0]->uid);
    }

    #[Test]
    public function paginate_public_filters_by_search(): void
    {
        $owner = User::factory()->create();

        Collection::create([
            'user_id' => $owner->id,
            'name' => 'Awesome Mods',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        Collection::create([
            'user_id' => $owner->id,
            'name' => 'Cool Stuff',
            'description' => 'great collection',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        Collection::create([
            'user_id' => $owner->id,
            'name' => 'Other',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        $byName = $this->service->paginatePublic(search: 'Awesome');
        $this->assertCount(1, $byName->items());
        $this->assertSame('Awesome Mods', $byName->items()[0]->name);

        $byDescription = $this->service->paginatePublic(search: 'great');
        $this->assertCount(1, $byDescription->items());
        $this->assertSame('Cool Stuff', $byDescription->items()[0]->name);
    }

    #[Test]
    public function paginate_public_orders_by_specified_column(): void
    {
        $owner = User::factory()->create();

        Collection::create([
            'user_id' => $owner->id,
            'name' => 'Alpha',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        Collection::create([
            'user_id' => $owner->id,
            'name' => 'Beta',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        $asc = $this->service->paginatePublic(orderBy: 'name', orderDirection: 'asc');
        $this->assertSame('Alpha', $asc->items()[0]->name);
        $this->assertSame('Beta', $asc->items()[1]->name);

        $desc = $this->service->paginatePublic(orderBy: 'name', orderDirection: 'desc');
        $this->assertSame('Beta', $desc->items()[0]->name);
        $this->assertSame('Alpha', $desc->items()[1]->name);
    }

    #[Test]
    public function paginate_for_owner_excludes_other_users_collections(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Collection::create([
            'user_id' => $user->id,
            'name' => 'My Collection',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        Collection::create([
            'user_id' => $other->id,
            'name' => 'Other Collection',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        $result = $this->service->paginateForOwner($user);

        $this->assertCount(1, $result->items());
        $this->assertSame('My Collection', $result->items()[0]->name);
    }

    #[Test]
    public function paginate_for_owner_excludes_system_collections_by_default(): void
    {
        $user = User::factory()->create();

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Regular',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PRIVATE,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        $result = $this->service->paginateForOwner($user);

        $this->assertCount(1, $result->items());
        $this->assertSame('Regular', $result->items()[0]->name);
    }

    #[Test]
    public function paginate_for_owner_includes_system_collections_when_not_excluded(): void
    {
        $user = User::factory()->create();

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Regular',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PRIVATE,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        $result = $this->service->paginateForOwner($user, excludeSystem: false);

        $this->assertCount(2, $result->items());
    }

    #[Test]
    public function paginate_for_owner_excludes_favorites_when_system_excluded(): void
    {
        $user = User::factory()->create();

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Regular',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PRIVATE,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        // With excludeSystem: true (default), favorites is excluded
        $withSystemExcluded = $this->service->paginateForOwner($user);
        $this->assertCount(1, $withSystemExcluded->items());
        $this->assertSame('Regular', $withSystemExcluded->items()[0]->name);

        // With excludeSystem: false, favorites is included
        $withSystemIncluded = $this->service->paginateForOwner($user, excludeSystem: false);
        $this->assertCount(2, $withSystemIncluded->items());
    }

    #[Test]
    public function paginate_for_owner_filters_by_visibility(): void
    {
        $user = User::factory()->create();

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Public',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Private',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $publicOnly = $this->service->paginateForOwner($user, visibility: 'public');
        $this->assertCount(1, $publicOnly->items());
        $this->assertSame('Public', $publicOnly->items()[0]->name);

        $privateOnly = $this->service->paginateForOwner($user, visibility: 'private');
        $this->assertCount(1, $privateOnly->items());
        $this->assertSame('Private', $privateOnly->items()[0]->name);

        $all = $this->service->paginateForOwner($user, visibility: 'all');
        $this->assertCount(2, $all->items());
    }

    #[Test]
    public function paginate_for_owner_with_entries_count_includes_count(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'With Entries',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        CollectionEntry::create([
            'collection_uid' => $collection->uid,
            'project_id' => $project->id,
            'sort_order' => 0,
        ]);

        $result = $this->service->paginateForOwner($user, withEntriesCount: true);
        $this->assertSame(1, (int) $result->items()[0]->entries_count);
    }

    #[Test]
    public function paginate_for_owner_with_projects_count_includes_distinct_count(): void
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'Multi Project',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        CollectionEntry::create([
            'collection_uid' => $collection->uid,
            'project_id' => $projectA->id,
            'sort_order' => 0,
        ]);

        CollectionEntry::create([
            'collection_uid' => $collection->uid,
            'project_id' => $projectB->id,
            'sort_order' => 1,
        ]);

        $result = $this->service->paginateForOwner($user, withProjectsCount: true);
        $this->assertSame(2, (int) $result->items()[0]->projects_count);
    }

    #[Test]
    public function get_by_uid_for_user_returns_collection_for_owner(): void
    {
        $user = User::factory()->create();

        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'My Collection',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $result = $this->service->getByUidForUser($collection->uid, $user);

        $this->assertNotNull($result);
        $this->assertSame($collection->uid, $result->uid);
    }

    #[Test]
    public function get_by_uid_for_user_returns_null_for_other_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'My Collection',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $result = $this->service->getByUidForUser($collection->uid, $other);

        $this->assertNull($result);
    }

    #[Test]
    public function get_by_uid_with_entries_returns_collection_with_eager_loads(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'With Entries',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        CollectionEntry::create([
            'collection_uid' => $collection->uid,
            'project_id' => $project->id,
            'sort_order' => 0,
        ]);

        $result = $this->service->getByUidWithEntries($collection->uid);

        $this->assertNotNull($result);
        $this->assertSame($collection->uid, $result->uid);
        $this->assertNotNull($result->user);
        $this->assertCount(1, $result->entries);
        $this->assertNotNull($result->entries[0]->project);
    }

    #[Test]
    public function get_by_uid_with_entries_returns_null_for_nonexistent(): void
    {
        $result = $this->service->getByUidWithEntries('nonexistent-uid');

        $this->assertNull($result);
    }

    #[Test]
    public function get_discoverable_by_uid_returns_public_non_favorites(): void
    {
        $owner = User::factory()->create();

        $public = Collection::create([
            'user_id' => $owner->id,
            'name' => 'Public',
            'visibility' => CollectionVisibility::PUBLIC,
        ]);

        $result = $this->service->getDiscoverableByUid($public->uid);

        $this->assertNotNull($result);
        $this->assertSame($public->uid, $result->uid);
    }

    #[Test]
    public function get_discoverable_by_uid_returns_null_for_private(): void
    {
        $owner = User::factory()->create();

        $private = Collection::create([
            'user_id' => $owner->id,
            'name' => 'Private',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $result = $this->service->getDiscoverableByUid($private->uid);

        $this->assertNull($result);
    }

    #[Test]
    public function get_discoverable_by_uid_returns_null_for_favorites(): void
    {
        $owner = User::factory()->create();

        $favorites = Collection::create([
            'user_id' => $owner->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PUBLIC,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        $result = $this->service->getDiscoverableByUid($favorites->uid);

        $this->assertNull($result);
    }

    #[Test]
    public function get_hidden_by_token_returns_collection_with_token(): void
    {
        $owner = User::factory()->create();

        $hidden = Collection::create([
            'user_id' => $owner->id,
            'name' => 'Hidden',
            'visibility' => CollectionVisibility::HIDDEN,
            'hidden_share_token' => 'secret-token',
        ]);

        $result = $this->service->getHiddenByToken('secret-token');

        $this->assertNotNull($result);
        $this->assertSame($hidden->uid, $result->uid);
    }

    #[Test]
    public function get_hidden_by_token_returns_null_for_wrong_token(): void
    {
        $owner = User::factory()->create();

        Collection::create([
            'user_id' => $owner->id,
            'name' => 'Hidden',
            'visibility' => CollectionVisibility::HIDDEN,
            'hidden_share_token' => 'secret-token',
        ]);

        $result = $this->service->getHiddenByToken('wrong-token');

        $this->assertNull($result);
    }

    #[Test]
    public function get_favorites_for_user_returns_favorites_collection(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $favorites = Collection::create([
            'user_id' => $user->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PRIVATE,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        CollectionEntry::create([
            'collection_uid' => $favorites->uid,
            'project_id' => $project->id,
            'sort_order' => 0,
        ]);

        $result = $this->service->getFavoritesForUser($user);

        $this->assertNotNull($result);
        $this->assertSame($favorites->uid, $result->uid);
        $this->assertSame(1, (int) $result->entries_count);
        $this->assertCount(1, $result->entries);
    }

    #[Test]
    public function get_favorites_for_user_returns_null_when_no_favorites(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getFavoritesForUser($user);

        $this->assertNull($result);
    }

    #[Test]
    public function is_in_any_collection_returns_true_when_project_in_collection(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'My Collection',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        CollectionEntry::create([
            'collection_uid' => $collection->uid,
            'project_id' => $project->id,
            'sort_order' => 0,
        ]);

        $result = $this->service->isInAnyCollection($user, $project);

        $this->assertTrue($result);
    }

    #[Test]
    public function is_in_any_collection_returns_false_when_project_not_in_collection(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Empty Collection',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $result = $this->service->isInAnyCollection($user, $project);

        $this->assertFalse($result);
    }

    #[Test]
    public function is_in_any_collection_excludes_system_collections(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $favorites = Collection::create([
            'user_id' => $user->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PRIVATE,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        CollectionEntry::create([
            'collection_uid' => $favorites->uid,
            'project_id' => $project->id,
            'sort_order' => 0,
        ]);

        $result = $this->service->isInAnyCollection($user, $project);

        $this->assertFalse($result);
    }

    #[Test]
    public function get_available_for_user_returns_non_system_collections(): void
    {
        $user = User::factory()->create();

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Regular',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Favorites',
            'visibility' => CollectionVisibility::PRIVATE,
            'system_type' => CollectionSystemType::FAVORITES,
        ]);

        $result = $this->service->getAvailableForUser($user);

        $this->assertCount(1, $result);
        $this->assertSame('Regular', $result[0]->name);
    }

    #[Test]
    public function get_available_for_user_with_project_id_includes_exists_flag(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'My Collection',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        CollectionEntry::create([
            'collection_uid' => $collection->uid,
            'project_id' => $project->id,
            'sort_order' => 0,
        ]);

        $result = $this->service->getAvailableForUser($user, $project->id);

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->includes_target_project);
    }

    #[Test]
    public function get_available_for_user_without_project_id_no_exists_flag(): void
    {
        $user = User::factory()->create();

        Collection::create([
            'user_id' => $user->id,
            'name' => 'My Collection',
            'visibility' => CollectionVisibility::PRIVATE,
        ]);

        $result = $this->service->getAvailableForUser($user);

        $this->assertCount(1, $result);
        $this->assertFalse(isset($result[0]->includes_target_project));
    }

    #[Test]
    public function visibility_options_returns_all_options(): void
    {
        $options = $this->service->visibilityOptions();

        $this->assertCount(4, $options);
        $this->assertSame('all', $options[0]['id']);
        $this->assertSame('public', $options[1]['id']);
        $this->assertSame('private', $options[2]['id']);
        $this->assertSame('hidden', $options[3]['id']);
    }
}
