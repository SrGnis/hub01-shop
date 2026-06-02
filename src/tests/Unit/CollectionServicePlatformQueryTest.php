<?php

namespace Tests\Unit;

use App\Enums\CollectionVisibility;
use App\Models\Collection;
use App\Models\CollectionEntry;
use App\Models\Project;
use App\Models\User;
use App\Services\CollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectionServicePlatformQueryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function paginate_scopes_to_owner_and_filters_search_visibility(): void
    {
        $service = new CollectionService();
        $user = User::factory()->create();
        $other = User::factory()->create();

        Collection::create(['user_id' => $other->id, 'name' => 'Other User', 'description' => 'outside', 'visibility' => CollectionVisibility::PUBLIC]);

        $public = Collection::create(['user_id' => $user->id, 'name' => 'Public Collection', 'description' => 'hello world', 'visibility' => CollectionVisibility::PUBLIC]);
        $private = Collection::create(['user_id' => $user->id, 'name' => 'Private Notes', 'description' => 'needle text', 'visibility' => CollectionVisibility::PRIVATE]);
        $hidden = Collection::create(['user_id' => $user->id, 'name' => 'Hidden Stuff', 'description' => null, 'visibility' => CollectionVisibility::HIDDEN]);

        $all = $service->paginateForOwner($user, perPage: 10);
        $allNames = collect($all->items())->pluck('name')->all();
        $this->assertContains('Public Collection', $allNames);
        $this->assertContains('Private Notes', $allNames);
        $this->assertContains('Hidden Stuff', $allNames);
        $this->assertNotContains('Other User', $allNames);

        $searchByName = $service->paginateForOwner($user, search: 'Public');
        $this->assertSame(['Public Collection'], collect($searchByName->items())->pluck('name')->all());

        $searchByDescription = $service->paginateForOwner($user, search: 'needle');
        $this->assertSame(['Private Notes'], collect($searchByDescription->items())->pluck('name')->all());

        $visibilityPublic = $service->paginateForOwner($user, visibility: 'public');
        $this->assertSame(['Public Collection'], collect($visibilityPublic->items())->pluck('name')->all());

        $visibilityPrivate = $service->paginateForOwner($user, visibility: 'private');
        $this->assertSame(['Private Notes'], collect($visibilityPrivate->items())->pluck('name')->all());

        $visibilityHidden = $service->paginateForOwner($user, visibility: 'hidden');
        $this->assertSame(['Hidden Stuff'], collect($visibilityHidden->items())->pluck('name')->all());
    }

    #[Test]
    public function exposes_entries_count_distinct_projects_count_and_per_page(): void
    {
        $service = new CollectionService();
        $user = User::factory()->create();

        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $c1 = Collection::create(['user_id' => $user->id, 'name' => 'One', 'visibility' => CollectionVisibility::PRIVATE]);
        $c2 = Collection::create(['user_id' => $user->id, 'name' => 'Two', 'visibility' => CollectionVisibility::PRIVATE]);
        $c3 = Collection::create(['user_id' => $user->id, 'name' => 'Three', 'visibility' => CollectionVisibility::PRIVATE]);

        CollectionEntry::create(['collection_uid' => $c1->uid, 'project_id' => $projectA->id, 'sort_order' => 0]);
        CollectionEntry::create(['collection_uid' => $c1->uid, 'project_id' => $projectB->id, 'sort_order' => 1]);

        $page = $service->paginateForOwner($user, perPage: 2, withEntriesCount: true, withProjectsCount: true);
        $this->assertCount(2, $page->items());

        $full = $service->paginateForOwner($user, perPage: 10, withEntriesCount: true, withProjectsCount: true);
        $row = collect($full->items())->firstWhere('uid', $c1->uid);
        $this->assertSame(2, (int) $row->entries_count);
        $this->assertSame(2, (int) $row->projects_count);
    }
}

