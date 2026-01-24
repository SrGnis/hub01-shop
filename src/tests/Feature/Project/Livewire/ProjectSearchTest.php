<?php

namespace Tests\Feature\Project\Livewire;

use App\Livewire\ProjectSearch;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectType;
use App\Models\ProjectVersionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectSearchTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectType = ProjectType::factory()->create();
    }

    #[Test]
    public function test_component_renders()
    {
        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->assertOk()
            ->assertViewIs('livewire.project-search');
    }

    #[Test]
    public function test_search_filters_projects_by_name()
    {
        $user = User::factory()->create();

        // Create projects with different names
        $project1 = Project::factory()->owner($user)->create(['name' => 'Amazing Project', 'project_type_id' => $this->projectType->id]);
        $project2 = Project::factory()->owner($user)->create(['name' => 'Another Cool Thing', 'project_type_id' => $this->projectType->id]);
        $project3 = Project::factory()->owner($user)->create(['name' => 'Something Different', 'project_type_id' => $this->projectType->id]);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('search', 'Amazing')
            ->assertSee($project1->name)
            ->assertDontSee($project2->name)
            ->assertDontSee($project3->name);
    }

    #[Test]
    public function test_filter_by_project_tags()
    {
        $user = User::factory()->create();

        // Create a tag for this project type
        $tag1 = ProjectTag::factory()->create();
        $tag1->projectTypes()->attach($this->projectType);

        $tag2 = ProjectTag::factory()->create();
        $tag2->projectTypes()->attach($this->projectType);

        // Create projects with different tags
        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project with Tag 1'
        ]);
        $project1->tags()->attach($tag1);

        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project with Tag 2'
        ]);
        $project2->tags()->attach($tag2);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('selectedTags', [$tag1->id])
            ->assertSee($project1->name)
            ->assertDontSee($project2->name);
    }

    #[Test]
    public function test_filter_by_version_tags()
    {
        $user = User::factory()->create();

        // Create version tags
        $versionTag = ProjectVersionTag::factory()->create();
        $versionTag->projectTypes()->attach($this->projectType);

        // Create project with version that has the tag
        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project with Version Tag'
        ]);
        $version1 = $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'changelog' => 'Initial release',
        ]);
        $version1->tags()->attach($versionTag);

        // Create project without the version tag
        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project without Version Tag'
        ]);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('selectedVersionTags', [$versionTag->id])
            ->assertSee($project1->name)
            ->assertDontSee($project2->name);
    }

    #[Test]
    public function test_order_by_name()
    {
        $user = User::factory()->create();

        $projectA = Project::factory()->owner($user)->create([
            'name' => 'Alpha Project',
            'project_type_id' => $this->projectType->id
        ]);
        $projectZ = Project::factory()->owner($user)->create([
            'name' => 'Zeta Project',
            'project_type_id' => $this->projectType->id
        ]);

        $component = Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('orderBy', 'name')
            ->set('orderDirection', 'asc');

        $projects = $component->get('projects');
        $this->assertEquals('Alpha Project', $projects->first()->name);
        $this->assertEquals('Zeta Project', $projects->last()->name);
    }

    #[Test]
    public function test_order_by_downloads()
    {
        $user = User::factory()->create();

        $projectLow = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Low Downloads Project'
        ]);
        $versionLow = $projectLow->versions()->create([
            'name' => 'Low Downloads Version',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'downloads' => 10,
        ]);

        $projectHigh = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'High Downloads Project'
        ]);
        $versionHigh = $projectHigh->versions()->create([
            'name' => 'High Downloads Version',
            'version' => '1.0.0',
            'release_date' => now(),
            'release_type' => 'release',
            'downloads' => 1000,
        ]);

        $component = Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('orderBy', 'downloads')
            ->set('orderDirection', 'desc');

        $projects = $component->get('projects');
        $this->assertEquals($projectHigh->id, $projects->first()->id);
    }

    #[Test]
    public function test_pagination_resets_on_search_change()
    {
        $user = User::factory()->create();

        // Create 15 projects to trigger pagination
        for ($i = 0; $i < 15; $i++) {
            Project::factory()->owner($user)->create([
                'project_type_id' => $this->projectType->id,
                'name' => 'Project ' . $i
            ]);
        }

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('resultsPerPage', 10)
            ->call('nextPage') // Go to page 2
            ->set('search', 'test') // Change search
            ->assertSet('paginators.page', 1); // Should reset to page 1
    }

    #[Test]
    public function test_clear_filters()
    {
        $tag = ProjectTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('search', 'test search')
            ->set('selectedTags', [$tag->id])
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('selectedTags', [])
            ->assertSet('selectedVersionTags', []);
    }

    #[Test]
    public function test_results_per_page_options()
    {
        $user = User::factory()->create();

        // Create 30 projects
        for ($i = 0; $i < 30; $i++) {
            Project::factory()->owner($user)->create([
                'project_type_id' => $this->projectType->id,
                'name' => 'Project ' . $i . ''
            ]);
        }

        $component = Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('resultsPerPage', 25);

        $projects = $component->get('projects');
        $this->assertEquals(25, $projects->count());
    }

    #[Test]
    public function test_only_approved_projects_are_shown()
    {
        $user = User::factory()->create();

        $approvedProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Approved Project',
        ]);

        $draftProject = Project::factory()->owner($user)->draft()->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Draft Project',
        ]);

        $pendingProject = Project::factory()->owner($user)->pending()->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Pending Project',
        ]);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->assertSee($approvedProject->name)
            ->assertDontSee($draftProject->name)
            ->assertDontSee($pendingProject->name);
    }

    #[Test]
    public function test_deactivated_projects_are_hidden()
    {
        $user = User::factory()->create();

        $activeProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Active Project',
        ]);

        $deactivatedProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'deactivated_at' => now(),
            'name' => 'Deactivated Project'
        ]);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->assertSee($activeProject->name)
            ->assertDontSee($deactivatedProject->name);
    }

    #[Test]
    public function test_only_shows_projects_of_correct_type()
    {
        $user = User::factory()->create();
        $otherProjectType = ProjectType::factory()->create();

        $correctTypeProject = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Correct Type Project',
        ]);

        $wrongTypeProject = Project::factory()->owner($user)->create([
            'project_type_id' => $otherProjectType->id,
            'name' => 'Wrong Type Project',
        ]);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->assertSee($correctTypeProject->name)
            ->assertDontSee($wrongTypeProject->name);
    }

    #[Test]
    public function test_filter_by_last_30_days()
    {
        $user = User::factory()->create();

        // Create project with version released 15 days ago
        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 15 days ago',
        ]);
        $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(15),
            'release_type' => 'release',
        ]);

        // Create project with version released 45 days ago
        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 45 days ago',
        ]);
        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(45),
            'release_type' => 'release',
        ]);

        // Create project with version released 60 days ago
        $project3 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 60 days ago',
        ]);
        $project3->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(60),
            'release_type' => 'release',
        ]);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('releaseDatePeriod', 'last_30_days')
            ->assertSee($project1->name)
            ->assertDontSee($project2->name)
            ->assertDontSee($project3->name);
    }

    #[Test]
    public function test_version_tag_and_release_date_filter_on_same_version()
    {
        $user = User::factory()->create();

        // Create version tag
        $versionTag = ProjectVersionTag::factory()->create();
        $versionTag->projectTypes()->attach($this->projectType);

        // Create project1: v1 (release_date 1 month ago, tag 1)
        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project with matching version',
        ]);
        $version1 = $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(30),
            'release_type' => 'release',
        ]);
        $version1->tags()->attach($versionTag);

        // Create project2: v1 (release_date 2 months ago, tag 1), v2 (release_date 1 month ago, tag 2)
        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project without matching version',
        ]);

        $version2Tag = ProjectVersionTag::factory()->create();
        $version2Tag->projectTypes()->attach($this->projectType);

        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(60),
            'release_type' => 'release',
        ])->tags()->attach($versionTag);

        $project2->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now()->subDays(30),
            'release_type' => 'release',
        ])->tags()->attach($version2Tag);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('selectedVersionTags', [$versionTag->id])
            ->set('releaseDatePeriod', 'last_30_days')
            ->assertSee($project1->name)
            ->assertDontSee($project2->name);
    }

    #[Test]
    public function test_filter_by_last_90_days()
    {
        $user = User::factory()->create();

        // Create project with version released 45 days ago
        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 45 days ago',
        ]);
        $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(45),
            'release_type' => 'release',
        ]);

        // Create project with version released 100 days ago
        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 100 days ago',
        ]);
        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(100),
            'release_type' => 'release',
        ]);

        // Create project with version released 120 days ago
        $project3 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 120 days ago',
        ]);
        $project3->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(120),
            'release_type' => 'release',
        ]);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('releaseDatePeriod', 'last_90_days')
            ->assertSee($project1->name)
            ->assertDontSee($project2->name)
            ->assertDontSee($project3->name);
    }

    #[Test]
    public function test_filter_by_last_year()
    {
        $user = User::factory()->create();

        // Create project with version released 6 months ago
        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 6 months ago',
        ]);
        $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subMonths(6),
            'release_type' => 'release',
        ]);

        // Create project with version released 14 months ago
        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 14 months ago',
        ]);
        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subMonths(14),
            'release_type' => 'release',
        ]);

        // Create project with version released 24 months ago
        $project3 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project 24 months ago',
        ]);
        $project3->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subMonths(24),
            'release_type' => 'release',
        ]);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('releaseDatePeriod', 'last_year')
            ->assertSee($project1->name)
            ->assertDontSee($project2->name)
            ->assertDontSee($project3->name);
    }

    #[Test]
    public function test_filter_by_custom_date_range()
    {
        $user = User::factory()->create();

        // Create project with version released on 2024-01-15
        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project January 2024',
        ]);
        $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => \Carbon\Carbon::parse('2024-01-15'),
            'release_type' => 'release',
        ]);

        // Create project with version released on 2024-02-15
        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project February 2024',
        ]);
        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => \Carbon\Carbon::parse('2024-02-15'),
            'release_type' => 'release',
        ]);

        // Create project with version released on 2024-03-15
        $project3 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project March 2024',
        ]);
        $project3->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => \Carbon\Carbon::parse('2024-03-15'),
            'release_type' => 'release',
        ]);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('releaseDatePeriod', 'custom')
            ->set('releaseDateStart', '2024-01-01')
            ->set('releaseDateEnd', '2024-02-28')
            ->assertSee($project1->name)
            ->assertSee($project2->name)
            ->assertDontSee($project3->name);
    }

    #[Test]
    public function test_multiple_version_tags_and_release_date_filter()
    {
        $user = User::factory()->create();

        // Create version tags
        $versionTag1 = ProjectVersionTag::factory()->create();
        $versionTag1->projectTypes()->attach($this->projectType);

        $versionTag2 = ProjectVersionTag::factory()->create();
        $versionTag2->projectTypes()->attach($this->projectType);

        // Create project1: v1 (release_date 1 month ago, tags: tag1, tag2)
        $project1 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project with both tags',
        ]);
        $version1 = $project1->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(30),
            'release_type' => 'release',
        ]);
        $version1->tags()->attach([$versionTag1->id, $versionTag2->id]);

        // Create project2: v1 (release_date 1 month ago, tag1), v2 (release_date 2 months ago, tag2)
        $project2 = Project::factory()->owner($user)->create([
            'project_type_id' => $this->projectType->id,
            'name' => 'Project without both tags',
        ]);

        $project2->versions()->create([
            'name' => 'Version 1.0.0',
            'version' => '1.0.0',
            'release_date' => now()->subDays(30),
            'release_type' => 'release',
        ])->tags()->attach($versionTag1);

        $project2->versions()->create([
            'name' => 'Version 2.0.0',
            'version' => '2.0.0',
            'release_date' => now()->subDays(60),
            'release_type' => 'release',
        ])->tags()->attach($versionTag2);

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('selectedVersionTags', [$versionTag1->id, $versionTag2->id])
            ->set('releaseDatePeriod', 'last_30_days')
            ->assertSee($project1->name)
            ->assertDontSee($project2->name);
    }

    #[Test]
    public function test_pagination_resets_on_release_date_filter_change()
    {
        $user = User::factory()->create();

        // Create 15 projects to trigger pagination
        for ($i = 0; $i < 15; $i++) {
            $project = Project::factory()->owner($user)->create([
                'project_type_id' => $this->projectType->id,
                'name' => 'Project ' . $i,
            ]);
            $project->versions()->create([
                'name' => 'Version 1.0.0',
                'version' => '1.0.0',
                'release_date' => now()->subDays($i),
                'release_type' => 'release',
            ]);
        }

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('resultsPerPage', 10)
            ->call('nextPage') // Go to page 2
            ->set('releaseDatePeriod', 'last_30_days') // Change release date filter
            ->assertSet('paginators.page', 1); // Should reset to page 1
    }

    #[Test]
    public function test_clear_filters_resets_release_date_filters()
    {
        $user = User::factory()->create();

        Livewire::test(ProjectSearch::class, ['projectType' => $this->projectType])
            ->set('releaseDatePeriod', 'last_30_days')
            ->set('releaseDateStart', '2024-01-01')
            ->set('releaseDateEnd', '2024-01-31')
            ->call('clearFilters')
            ->assertSet('releaseDatePeriod', 'all')
            ->assertSet('releaseDateStart', null)
            ->assertSet('releaseDateEnd', null);
    }
}
