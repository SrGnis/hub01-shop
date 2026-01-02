<?php

namespace Tests\Feature\Project\Livewire;

use App\Livewire\ProjectVersionShow;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectVersionShowTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;
    private User $user;
    private Project $project;
    private ProjectVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectType = ProjectType::factory()->create();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $this->version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
        ]);
    }

    #[Test]
    public function test_component_renders_successfully()
    {
        Livewire::test(ProjectVersionShow::class, [
            'project' => $this->project->slug,
            'version_key' => $this->version->version,
        ])
            ->assertOk()
            ->assertViewIs('livewire.project-version-show');
    }

    #[Test]
    public function test_project_computed_property_loads_data()
    {
        $component = Livewire::test(ProjectVersionShow::class, [
            'project' => $this->project->slug,
            'version_key' => $this->version->version,
        ]);

        $project = $component->get('project');

        $this->assertNotNull($project);
        $this->assertEquals($this->project->id, $project->id);
        $this->assertEquals($this->project->slug, $project->slug);
    }

    #[Test]
    public function test_version_computed_property_loads_data()
    {
        $component = Livewire::test(ProjectVersionShow::class, [
            'project' => $this->project->slug,
            'version_key' => $this->version->version,
        ]);

        $version = $component->get('version');

        $this->assertNotNull($version);
        $this->assertEquals($this->version->id, $version->id);
        $this->assertEquals($this->version->version, $version->version);
    }

    #[Test]
    public function test_version_loads_with_relationships()
    {
        // Add files
        $this->version->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/test.zip',
            'size' => 1024,
        ]);

        // Add dependency
        $dependencyProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $this->version->dependencies()->create([
            'dependency_project_id' => $dependencyProject->id,
            'dependency_type' => 'required',
        ]);

        // Add tags
        $tag = ProjectVersionTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);
        $this->version->tags()->attach($tag);

        $component = Livewire::test(ProjectVersionShow::class, [
            'project' => $this->project->slug,
            'version_key' => $this->version->version,
        ]);

        $version = $component->get('version');

        $this->assertCount(1, $version->files);
        $this->assertCount(1, $version->dependencies);
        $this->assertCount(1, $version->tags);
    }

    #[Test]
    public function test_cannot_view_deactivated_project_version()
    {
        $this->project->update(['deactivated_at' => now()]);

        $this->get(route('project.version.show', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version_key' => $this->version->version,
        ]))
            ->assertRedirect(route('project-search', ['projectType' => $this->projectType]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function test_can_view_active_project_version()
    {
        $this->get(route('project.version.show', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version_key' => $this->version->version,
        ]))
            ->assertOk();
    }

    #[Test]
    public function test_nonexistent_project_returns_404()
    {
        $this->get(route('project.version.show', [
            'projectType' => $this->projectType,
            'project' => 'nonexistent-project',
            'version_key' => $this->version->version,
        ]))
            ->assertNotFound();
    }

    #[Test]
    public function test_nonexistent_version_returns_404()
    {
        $this->get(route('project.version.show', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version_key' => 'nonexistent-version',
        ]))
            ->assertNotFound();
    }
}
