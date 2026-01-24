<?php

namespace Tests\Feature\Project\Livewire;

use App\Livewire\ProjectShow;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\ProjectVersionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_component_renders_for_approved_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->assertOk()
            ->assertViewIs('livewire.project-show')
            ->assertSee($project->name);
    }

    #[Test]
    public function test_guest_can_view_approved_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        Livewire::test(ProjectShow::class, ['project' => $project->slug])
            ->assertOk()
            ->assertSee($project->name);
    }

    #[Test]
    public function test_deactivated_project_redirects()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create([
            'deactivated_at' => now()
        ]);

        $this->actingAs($user)
            ->get(route('project.show', [
                'projectType' => $project->projectType,
                'project' => $project
            ]))
            ->assertRedirect(route('project-search', ['projectType' => $project->projectType]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function test_member_can_view_draft_project()
    {
        $owner = User::factory()->create();
        $project = Project::factory()->owner($owner)->draft()->create();

        Livewire::actingAs($owner)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->assertOk()
            ->assertSee($project->name);
    }

    #[Test]
    public function test_non_member_cannot_view_draft_project()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->owner($owner)->draft()->create();

        $this->actingAs($otherUser)
            ->get(route('project.show', [
                'projectType' => $project->projectType,
                'project' => $project
            ]))
            ->assertNotFound();
    }

    #[Test]
    public function test_member_can_view_pending_project()
    {
        $owner = User::factory()->create();
        $project = Project::factory()->owner($owner)->pending()->create();

        Livewire::actingAs($owner)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->assertOk()
            ->assertSee($project->name);
    }

    #[Test]
    public function test_non_member_cannot_view_pending_project()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->owner($owner)->pending()->create();

        $this->actingAs($otherUser)
            ->get(route('project.show', [
                'projectType' => $project->projectType,
                'project' => $project
            ]))
            ->assertNotFound();
    }

    #[Test]
    public function test_active_tab_can_be_set_via_parameter()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug, 'activeTab' => 'versions'])
            ->assertSet('activeTab', 'versions');
    }

    // Removed functionality
    // #[Test]
    // public function test_invalid_active_tab_defaults_to_description()
    // {
    //     $user = User::factory()->create();
    //     $project = Project::factory()->owner($user)->create();

    //     Livewire::actingAs($user)
    //         ->test(ProjectShow::class, ['project' => $project->slug, 'activeTab' => 'invalid'])
    //         ->assertSet('activeTab', 'description');
    // }

    #[Test]
    public function test_versions_are_paginated()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        // Create 15 versions
        for ($i = 1; $i <= 15; $i++) {
            $project->versions()->create([
                'name' => "Version 1.0.$i",
                'version' => "1.0.$i",
                'release_date' => now()->subDays($i),
                'release_type' => 'release',
            ]);
        }

        $component = Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->set('versionsPerPage', 10);

        $versions = $component->get('versions');
        $this->assertEquals(10, $versions->count());
        $this->assertEquals(15, $versions->total());
    }

    #[Test]
    public function test_versions_are_sorted_by_release_date_descending()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        $oldVersion = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(10),
            'release_type' => 'release',
        ]);

        $newVersion = $project->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now(),
            'release_type' => 'release',
        ]);

        $component = Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug]);

        $versions = $component->get('versions');
        $this->assertEquals($newVersion->id, $versions->first()->id);
        $this->assertEquals($oldVersion->id, $versions->last()->id);
    }

    #[Test]
    public function test_changelog_versions_only_shows_versions_with_changelog()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        $versionWithChangelog = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'changelog' => 'This is a changelog',
        ]);

        $versionWithoutChangelog = $project->versions()->create([
            'name' => 'Version 1.0.1',
            'version' => '1.0.1',
            'release_date' => now()->subDay(),
            'release_type' => 'release',
            'changelog' => null,
        ]);

        $component = Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug]);

        $changelogVersions = $component->get('changelogVersions');
        $this->assertEquals(1, $changelogVersions->total());
        $this->assertEquals($versionWithChangelog->id, $changelogVersions->first()->id);
    }

    #[Test]
    public function test_changelog_pagination()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        // Create 15 versions with changelogs
        for ($i = 1; $i <= 15; $i++) {
            $project->versions()->create([
                'name' => "Version 1.0.$i",
                'version' => "1.0.$i",
                'release_date' => now()->subDays($i),
                'release_type' => 'release',
                'changelog' => "Changelog for version 1.0.$i",
            ]);
        }

        $component = Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->set('changelogPerPage', 10);

        $changelogVersions = $component->get('changelogVersions');
        $this->assertEquals(10, $changelogVersions->count());
        $this->assertEquals(15, $changelogVersions->total());
    }

    #[Test]
    public function test_versions_pagination_resets_when_per_page_changes()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        // Create 20 versions
        for ($i = 1; $i <= 20; $i++) {
            $project->versions()->create([
                'name' => "Version 1.0.$i",
                'version' => "1.0.$i",
                'release_date' => now()->subDays($i),
                'release_type' => 'release',
            ]);
        }

        Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->set('versionsPerPage', 10)
            ->call('nextPage', 'versionsPage') // Go to page 2
            ->set('versionsPerPage', 20) // Change per page
            ->assertSet('paginators.versionsPage', 1); // Should reset to page 1
    }

    #[Test]
    public function test_changelog_pagination_resets_when_per_page_changes()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        // Create 20 versions with changelog
        for ($i = 1; $i <= 20; $i++) {
            $project->versions()->create([
                'name' => "Version 1.0.$i",
                'version' => "1.0.$i",
                'release_date' => now()->subDays($i),
                'release_type' => 'release',
                'changelog' => "Changelog $i",
            ]);
        }

        Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->set('changelogPerPage', 10)
            ->call('nextPage', 'changelogPage') // Go to page 2
            ->set('changelogPerPage', 20) // Change per page
            ->assertSet('paginators.changelogPage', 1); // Should reset to page 1
    }

    #[Test]
    public function test_filter_versions_by_last_30_days()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        // Create versions with different release dates
        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(15),
            'release_type' => 'release',
        ]);

        $version2 = $project->versions()->create([
            'name' => 'Version 1.0.1',
            'version' => '1.0.1',
            'release_date' => now()->subDays(45),
            'release_type' => 'release',
        ]);

        $version3 = $project->versions()->create([
            'name' => 'Version 1.0.2',
            'version' => '1.0.2',
            'release_date' => now()->subDays(60),
            'release_type' => 'release',
        ]);

        $component = Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->set('releaseDatePeriod', 'last_30_days');

        $versions = $component->get('versions');
        $this->assertEquals(1, $versions->count());
        $this->assertEquals($version1->id, $versions->first()->id);
    }

    #[Test]
    public function test_filter_versions_by_custom_date_range()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        // Create versions with different release dates
        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => \Carbon\Carbon::parse('2024-01-15'),
            'release_type' => 'release',
        ]);

        $version2 = $project->versions()->create([
            'name' => 'Version 1.0.1',
            'version' => '1.0.1',
            'release_date' => \Carbon\Carbon::parse('2024-02-15'),
            'release_type' => 'release',
        ]);

        $version3 = $project->versions()->create([
            'name' => 'Version 1.0.2',
            'version' => '1.0.2',
            'release_date' => \Carbon\Carbon::parse('2024-03-15'),
            'release_type' => 'release',
        ]);

        $component = Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->set('releaseDatePeriod', 'custom')
            ->set('releaseDateStart', '2024-01-01')
            ->set('releaseDateEnd', '2024-02-28');

        $versions = $component->get('versions');
        $this->assertEquals(2, $versions->count());
        $this->assertEquals($version2->id, $versions->first()->id);
        $this->assertEquals($version1->id, $versions->last()->id);
    }

    #[Test]
    public function test_version_tag_and_release_date_filter_on_same_version()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        // Create version tag
        $versionTag = ProjectVersionTag::factory()->create();
        $versionTag->projectTypes()->attach($project->projectType);

        // Create versions with different tags and release dates
        $version1 = $project->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(30),
            'release_type' => 'release',
        ]);
        $version1->tags()->attach($versionTag);

        $version2Tag = ProjectVersionTag::factory()->create();
        $version2Tag->projectTypes()->attach($project->projectType);

        $version2 = $project->versions()->create([
            'name' => 'Version 1.0.1',
            'version' => '1.0.1',
            'release_date' => now()->subDays(60),
            'release_type' => 'release',
        ]);
        $version2->tags()->attach($versionTag);

        $version3Tag = ProjectVersionTag::factory()->create();
        $version3Tag->projectTypes()->attach($project->projectType);

        $version3 = $project->versions()->create([
            'name' => 'Version 1.0.2',
            'version' => '1.0.2',
            'release_date' => now()->subDays(30),
            'release_type' => 'release',
        ]);
        $version3->tags()->attach($version3Tag);

        $component = Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->set('selectedVersionTags', [$versionTag->id])
            ->set('releaseDatePeriod', 'last_30_days');

        $versions = $component->get('versions');
        $this->assertEquals(1, $versions->count());
        $this->assertEquals($version1->id, $versions->first()->id);
    }

    #[Test]
    public function test_versions_pagination_resets_on_release_date_filter_change()
    {
        $user = User::factory()->create();
        $project = Project::factory()->owner($user)->create();

        // Create 15 versions
        for ($i = 1; $i <= 15; $i++) {
            $project->versions()->create([
                'name' => "Version 1.0.$i",
                'version' => "1.0.$i",
                'release_date' => now()->subDays($i),
                'release_type' => 'release',
            ]);
        }

        Livewire::actingAs($user)
            ->test(ProjectShow::class, ['project' => $project->slug])
            ->set('versionsPerPage', 10)
            ->call('nextPage', 'versionsPage') // Go to page 2
            ->set('releaseDatePeriod', 'last_30_days') // Change release date filter
            ->assertSet('paginators.versionsPage', 1); // Should reset to page 1
    }
}
