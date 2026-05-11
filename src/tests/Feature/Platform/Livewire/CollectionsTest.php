<?php

namespace Tests\Feature\Platform\Livewire;

use App\Enums\CollectionVisibility;
use App\Livewire\Platform\Collections;
use App\Models\Collection;
use App\Models\CollectionEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lists_only_owned_collections(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Collection::create(['user_id' => $user->id, 'name' => 'Mine', 'visibility' => CollectionVisibility::PUBLIC]);
        Collection::create(['user_id' => $other->id, 'name' => 'Other', 'visibility' => CollectionVisibility::PUBLIC]);

        $this->actingAs($user);

        Livewire::test(Collections::class)
            ->assertSee('Mine')
            ->assertDontSee('Other');
    }

    #[Test]
    public function search_and_visibility_filters_apply_correctly(): void
    {
        $user = User::factory()->create();

        Collection::create(['user_id' => $user->id, 'name' => 'Public Alpha', 'description' => 'First', 'visibility' => CollectionVisibility::PUBLIC]);
        Collection::create(['user_id' => $user->id, 'name' => 'Private Beta', 'description' => 'Second needle', 'visibility' => CollectionVisibility::PRIVATE]);
        Collection::create(['user_id' => $user->id, 'name' => 'Hidden Gamma', 'description' => 'Third', 'visibility' => CollectionVisibility::HIDDEN]);

        $this->actingAs($user);

        Livewire::test(Collections::class)
            ->set('search', 'needle')
            ->assertSee('Private Beta')
            ->assertDontSee('Public Alpha')
            ->set('search', '')
            ->set('visibility', 'public')
            ->assertSee('Public Alpha')
            ->assertDontSee('Private Beta')
            ->assertDontSee('Hidden Gamma')
            ->set('visibility', 'private')
            ->assertSee('Private Beta')
            ->assertDontSee('Public Alpha')
            ->set('visibility', 'hidden')
            ->assertSee('Hidden Gamma')
            ->assertDontSee('Public Alpha');
    }

    #[Test]
    public function entries_count_and_distinct_projects_count_are_exposed(): void
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $collection = Collection::create(['user_id' => $user->id, 'name' => 'Metrics', 'visibility' => CollectionVisibility::PRIVATE]);

        CollectionEntry::create(['collection_uid' => $collection->uid, 'project_id' => $projectA->id, 'sort_order' => 0]);
        CollectionEntry::create(['collection_uid' => $collection->uid, 'project_id' => $projectB->id, 'sort_order' => 1]);

        $this->actingAs($user);

        $rows = Livewire::test(Collections::class)->get('collections');
        $row = $rows->firstWhere('uid', $collection->uid);

        $this->assertSame(2, (int) $row->entries_count);
        $this->assertSame(2, (int) $row->projects_count);
    }

    #[Test]
    public function updating_search_and_visibility_resets_page(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 15; $i++) {
            Collection::create(['user_id' => $user->id, 'name' => 'C' . $i, 'visibility' => CollectionVisibility::PRIVATE]);
        }

        $this->actingAs($user);

        Livewire::test(Collections::class)
            ->set('perPage', 10)
            ->call('nextPage')
            ->set('search', 'C1')
            ->assertSet('paginators.page', 1)
            ->call('nextPage')
            ->set('visibility', 'private')
            ->assertSet('paginators.page', 1);
    }
}

