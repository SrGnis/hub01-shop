<?php

namespace Tests\Feature\User\Livewire;

use App\Livewire\UserProfile;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectTagGroup;
use App\Models\User;
use App\Models\Membership;
use App\Models\ProjectVersionDailyDownload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_user_profile_component_renders()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->assertOk()
            ->assertViewIs('livewire.user-profile');
    }

    #[Test]
    public function test_active_projects_computed_property()
    {
        $user = User::factory()->create();

        // Create 2 active projects owned by user
        $project1 = Project::factory()->owner($user)->create(['created_at' => now()->subDay()]);
        $project2 = Project::factory()->owner($user)->create(['created_at' => now()]);

        // Create 1 project NOT owned by user
        Project::factory()->create();

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->assertCount('activeProjects', 2);
    }

    #[Test]
    public function test_profile_projects_can_be_sorted_by_name()
    {
        $user = User::factory()->create();

        Project::factory()->owner($user)->create(['name' => 'Zulu Project', 'slug' => 'zulu-project']);
        Project::factory()->owner($user)->create(['name' => 'Alpha Project', 'slug' => 'alpha-project']);

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->set('orderBy', 'name')
            ->set('orderDirection', 'asc')
            ->assertSeeInOrder(['Alpha Project', 'Zulu Project']);
    }

    #[Test]
    public function test_profile_projects_can_be_filtered_by_project_tag()
    {
        $user = User::factory()->create();
        $tagGroup = ProjectTagGroup::factory()->create(['name' => 'Category']);
        $tag = ProjectTag::factory()->create([
            'name' => 'Gameplay',
            'project_tag_group_id' => $tagGroup->id,
        ]);

        $matchingProject = Project::factory()->owner($user)->create(['name' => 'Tagged Project', 'slug' => 'tagged-project']);
        $matchingProject->tags()->attach($tag);
        Project::factory()->owner($user)->create(['name' => 'Untagged Project', 'slug' => 'untagged-project']);

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->set('selectedTags', [$tag->id])
            ->assertSee('Tagged Project')
            ->assertDontSee('Untagged Project');
    }

    #[Test]
    public function test_profile_project_filters_can_be_cleared()
    {
        $user = User::factory()->create();
        $tag = ProjectTag::factory()->create();

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->set('projectSearch', 'test')
            ->set('selectedTags', [$tag->id])
            ->set('selectedVersionTags', [123])
            ->set('releaseDatePeriod', 'last_30_days')
            ->call('clearProjectFilters')
            ->assertSet('projectSearch', '')
            ->assertSet('selectedTags', [])
            ->assertSet('selectedVersionTags', [])
            ->assertSet('releaseDatePeriod', 'all')
            ->assertSet('releaseDateStart', null)
            ->assertSet('releaseDateEnd', null);
    }

    #[Test]
    public function test_owned_projects_count()
    {
        $user = User::factory()->create();

        // 2 Owned projects
        Project::factory()->owner($user)->create();
        Project::factory()->owner($user)->create();

        // 1 Contribution (not owned)
        $project3 = Project::factory()->create();
        $membership = new Membership([
            'role' => 'editor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($user);
        $membership->project()->associate($project3);
        $membership->save();

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->assertSet('ownedProjectsCount', 2);
    }

    #[Test]
    public function test_contributions_count()
    {
        $user = User::factory()->create();

        // 1 Owned project
        Project::factory()->owner($user)->create();

        // 2 Contributions (not owned)
        $project2 = Project::factory()->create();
        $membership = new Membership([
            'role' => 'editor',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($user);
        $membership->project()->associate($project2);
        $membership->save();

        $project3 = Project::factory()->create();
        $membership = new Membership([
            'role' => 'viewer',
            'primary' => false,
            'status' => 'active'
        ]);
        $membership->user()->associate($user);
        $membership->project()->associate($project3);
        $membership->save();

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->assertSet('contributionsCount', 2);
    }

    #[Test]
    public function test_aggregate_downloads_includes_owned_and_active_contributor_projects()
    {
        $user = User::factory()->create();

        $ownedProject = Project::factory()->owner($user)->create();
        $ownedProject->versions()->create([
            'name' => 'Owned v1',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->toDateString(),
        ]);
        ProjectVersionDailyDownload::factory()
            ->forVersion($ownedProject->versions()->first())
            ->forDate(now()->toDateString())
            ->withDownloads(12)
            ->create();

        $contributorProject = Project::factory()->create();
        Membership::factory()->create([
            'user_id' => $user->id,
            'project_id' => $contributorProject->id,
            'primary' => false,
            'status' => 'active',
        ]);
        $contributorProject->versions()->create([
            'name' => 'Contributor v1',
            'version' => '2.0.0',
            'release_type' => 'release',
            'release_date' => now()->toDateString(),
        ]);
        ProjectVersionDailyDownload::factory()
            ->forVersion($contributorProject->versions()->first())
            ->forDate(now()->toDateString())
            ->withDownloads(7)
            ->create();

        $inactiveContributorProject = Project::factory()->create();
        Membership::factory()->create([
            'user_id' => $user->id,
            'project_id' => $inactiveContributorProject->id,
            'primary' => false,
            'status' => 'pending',
        ]);
        $inactiveContributorProject->versions()->create([
            'name' => 'Pending v1',
            'version' => '3.0.0',
            'release_type' => 'release',
            'release_date' => now()->toDateString(),
        ]);
        ProjectVersionDailyDownload::factory()
            ->forVersion($inactiveContributorProject->versions()->first())
            ->forDate(now()->toDateString())
            ->withDownloads(100)
            ->create();

        Livewire::actingAs($user)
            ->test(UserProfile::class, ['user' => $user])
            ->assertSet('aggregateDownloads', 19)
            ->assertSee('19 downloads');
    }

}
