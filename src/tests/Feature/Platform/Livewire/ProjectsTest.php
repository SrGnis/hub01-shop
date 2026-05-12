<?php

namespace Tests\Feature\Platform\Livewire;

use App\Livewire\Platform\Projects;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lists_only_active_membership_projects(): void
    {
        $user = User::factory()->create();
        $type = ProjectType::factory()->create();

        $activeProject = Project::factory()->create(['project_type_id' => $type->id, 'name' => 'Active One']);
        $inactiveProject = Project::factory()->create(['project_type_id' => $type->id, 'name' => 'Inactive One']);

        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $activeProject->id, 'status' => 'active']);
        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $inactiveProject->id, 'status' => 'pending']);

        $this->actingAs($user);

        Livewire::test(Projects::class)
            ->assertSee('Active One')
            ->assertDontSee('Inactive One');
    }

    #[Test]
    public function search_filters_by_name_and_slug(): void
    {
        $user = User::factory()->create();
        $type = ProjectType::factory()->create();
        $p1 = Project::factory()->create(['project_type_id' => $type->id, 'name' => 'Alpha Name', 'slug' => 'alpha-name']);
        $p2 = Project::factory()->create(['project_type_id' => $type->id, 'name' => 'Bravo Name', 'slug' => 'bravo-slug']);
        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $p1->id, 'status' => 'active']);
        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $p2->id, 'status' => 'active']);

        $this->actingAs($user);

        Livewire::test(Projects::class)
            ->set('search', 'Alpha')
            ->assertSee('Alpha Name')
            ->assertDontSee('Bravo Name');

        Livewire::test(Projects::class)
            ->set('search', 'bravo-slug')
            ->assertSee('Bravo Name')
            ->assertDontSee('Alpha Name');
    }

    #[Test]
    public function type_status_sort_and_per_page_behave_as_expected(): void
    {
        $user = User::factory()->create();
        $typeA = ProjectType::factory()->create(['value' => 'platform_mod_a', 'display_name' => 'Mods A']);
        $typeB = ProjectType::factory()->create(['value' => 'platform_sound_b', 'display_name' => 'Sound B']);

        $a = Project::factory()->create(['project_type_id' => $typeA->id, 'name' => 'Alpha', 'slug' => 'slug-a', 'status' => 'active']);
        $b = Project::factory()->create(['project_type_id' => $typeB->id, 'name' => 'Beta', 'slug' => 'slug-b', 'status' => 'inactive']);
        $c = Project::factory()->create(['project_type_id' => $typeA->id, 'name' => 'Gamma', 'slug' => 'slug-c', 'status' => 'active']);

        foreach ([$a, $b, $c] as $p) {
            Membership::factory()->create(['user_id' => $user->id, 'project_id' => $p->id, 'status' => 'active']);
        }

        $this->actingAs($user);

        Livewire::test(Projects::class)
            ->set('type', 'platform_mod_a')
            ->assertSee('Alpha')
            ->assertSee('Gamma')
            ->assertDontSee('Beta');

        $component = Livewire::test(Projects::class)
            ->set('type', 'all')
            ->set('status', 'inactive')
            ->assertSee('Beta')
            ->assertDontSee('Alpha')
            ->set('status', 'all')
            ->set('sortBy', ['column' => 'name', 'direction' => 'desc'])
            ->set('perPage', 2);

        $rows = $component->get('projects');
        $this->assertSame(2, $rows->count());
        $this->assertSame('Gamma', $rows->first()->name);
    }

    #[Test]
    public function updating_filters_resets_pagination_and_session_state_is_hydrated(): void
    {
        $user = User::factory()->create();
        $type = ProjectType::factory()->create();

        for ($i = 0; $i < 15; $i++) {
            $project = Project::factory()->create(['project_type_id' => $type->id, 'name' => 'Proj ' . $i]);
            Membership::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'status' => 'active']);
        }

        $this->actingAs($user);

        Livewire::withQueryParams([
            'platform-projects-search' => 'abc',
            'platform-projects-type' => 'all',
            'platform-projects-status' => 'all',
            'platform-projects-per-page' => 10,
        ])->test(Projects::class)
            ->set('perPage', 10)
            ->call('nextPage')
            ->set('search', 'Proj')
            ->assertSet('paginators.page', 1)
            ->set('type', 'all')
            ->assertSet('paginators.page', 1)
            ->set('status', 'all')
            ->assertSet('paginators.page', 1)
            ->set('perPage', 25)
            ->assertSet('paginators.page', 1);
    }
}
