<?php

namespace Tests\Unit;

use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use App\Services\ProjectQuotaService;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectServicePlatformQueryTest extends TestCase
{
    use RefreshDatabase;

    private ProjectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectService($this->app->make(ProjectQuotaService::class));
    }

    #[Test]
    public function returns_only_active_membership_projects_and_supports_filters(): void
    {
        $user = User::factory()->create();
        $mod = ProjectType::factory()->create(['value' => 'platform_query_mod', 'display_name' => 'Query Mods']);
        $sound = ProjectType::factory()->create(['value' => 'platform_query_sound', 'display_name' => 'Query Sounds']);

        $visible = Project::factory()->create(['name' => 'Alpha', 'slug' => 'alpha-slug', 'project_type_id' => $mod->id, 'status' => 'active']);
        $hiddenInactiveMembership = Project::factory()->create(['name' => 'Beta', 'slug' => 'beta-slug', 'project_type_id' => $mod->id, 'status' => 'active']);
        $otherType = Project::factory()->create(['name' => 'Gamma', 'slug' => 'gamma-slug', 'project_type_id' => $sound->id, 'status' => 'inactive']);

        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $visible->id, 'status' => 'active']);
        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $hiddenInactiveMembership->id, 'status' => 'pending']);
        Membership::factory()->create(['user_id' => $user->id, 'project_id' => $otherType->id, 'status' => 'active']);

        $all = $this->service->projectsForUser($user);
        $ids = collect($all->items())->pluck('id')->all();
        $this->assertContains($visible->id, $ids);
        $this->assertContains($otherType->id, $ids);
        $this->assertNotContains($hiddenInactiveMembership->id, $ids);

        $byName = $this->service->projectsForUser($user, search: 'Alpha');
        $this->assertSame([$visible->id], collect($byName->items())->pluck('id')->all());

        $bySlug = $this->service->projectsForUser($user, search: 'gamma-slug');
        $this->assertSame([$otherType->id], collect($bySlug->items())->pluck('id')->all());

        $byType = $this->service->projectsForUser($user, type: 'platform_query_mod');
        $this->assertSame([$visible->id], collect($byType->items())->pluck('id')->all());

        $byStatus = $this->service->projectsForUser($user, status: 'inactive');
        $this->assertSame([$otherType->id], collect($byStatus->items())->pluck('id')->all());
    }

    #[Test]
    public function sort_and_pagination_fallbacks_work_for_platform_query(): void
    {
        $user = User::factory()->create();
        $typeA = ProjectType::factory()->create(['value' => 'aaa', 'display_name' => 'AAA']);
        $typeB = ProjectType::factory()->create(['value' => 'zzz', 'display_name' => 'ZZZ']);

        $a = Project::factory()->create(['name' => 'Charlie', 'slug' => 'slug-c', 'status' => 'inactive', 'project_type_id' => $typeB->id]);
        $b = Project::factory()->create(['name' => 'Alpha', 'slug' => 'slug-a', 'status' => 'active', 'project_type_id' => $typeA->id]);
        $c = Project::factory()->create(['name' => 'Bravo', 'slug' => 'slug-b', 'status' => 'active', 'project_type_id' => $typeB->id]);

        foreach ([$a, $b, $c] as $project) {
            Membership::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'status' => 'active']);
        }

        $invalidSort = $this->service->projectsForUser($user, sortBy: ['column' => 'not-real', 'direction' => 'sideways']);
        $this->assertSame(['Alpha', 'Bravo', 'Charlie'], collect($invalidSort->items())->pluck('name')->all());

        $bySlugDesc = $this->service->projectsForUser($user, sortBy: ['column' => 'slug', 'direction' => 'desc']);
        $this->assertSame(['slug-c', 'slug-b', 'slug-a'], collect($bySlugDesc->items())->pluck('slug')->all());

        $byStatusAsc = $this->service->projectsForUser($user, sortBy: ['column' => 'status', 'direction' => 'asc']);
        $this->assertSame(['active', 'active', 'inactive'], collect($byStatusAsc->items())->pluck('status')->all());

        $byTypeAsc = $this->service->projectsForUser($user, sortBy: ['column' => 'type', 'direction' => 'asc']);
        $this->assertSame(['AAA', 'ZZZ', 'ZZZ'], collect($byTypeAsc->items())->pluck('projectType.display_name')->all());

        $perPage = $this->service->projectsForUser($user, perPage: 2);
        $this->assertCount(2, $perPage->items());
    }
}
