<?php

namespace Tests\Feature\Project\Livewire;

use App\Enums\ApprovalStatus;
use App\Livewire\ProjectCreateModal;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectCreateModalTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('projects.auto_approve', false);
        $this->projectType = ProjectType::factory()->create();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function test_component_renders()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->assertOk()
            ->assertViewIs('livewire.project-create-modal')
            ->assertSet('isOpen', false)
            ->assertSet('selectedType', null)
            ->assertSet('name', '')
            ->assertSet('slug', '')
            ->assertSet('summary', '');
    }

    #[Test]
    public function test_modal_opens_with_event()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->assertSet('isOpen', true);
    }

    #[Test]
    public function test_modal_opens_with_pre_selected_type()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open', $this->projectType->value)
            ->assertSet('isOpen', true)
            ->assertSet('selectedType', $this->projectType->value);
    }

     #[Test]
    public function test_guest_cannot_open_modal()
    {
        Livewire::test(ProjectCreateModal::class)
            ->call('open')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function test_modal_closes()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->assertSet('isOpen', true)
            ->call('close')
            ->assertSet('isOpen', false);
    }

    #[Test]
    public function test_slug_generates_from_name()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->set('name', 'Test Project Name')
            ->assertSet('slug', 'test-project-name');
    }

    #[Test]
    public function test_validation_rules()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->set('selectedType', null)
            ->set('name', '')
            ->set('slug', '')
            ->set('summary', '')
            ->call('create')
            ->assertHasErrors(['selectedType', 'name', 'slug', 'summary']);
    }

    #[Test]
    public function test_slug_validation_format()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->set('selectedType', $this->projectType->value)
            ->set('name', 'Test')
            ->set('slug', 'Invalid Slug!')
            ->set('summary', 'Test summary')
            ->call('create')
            ->assertHasErrors(['slug']);
    }

    #[Test]
    public function test_slug_validation_unique()
    {
        $existingProject = Project::factory()->create([
            'slug' => 'existing-slug',
            'project_type_id' => $this->projectType->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->set('selectedType', $this->projectType->value)
            ->set('name', 'Test')
            ->set('slug', 'existing-slug')
            ->set('summary', 'Test summary')
            ->call('create')
            ->assertHasErrors(['slug']);
    }

    #[Test]
    public function test_create_project_successfully()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->set('selectedType', $this->projectType->value)
            ->set('name', 'New Test Project')
            ->set('slug', 'new-test-project')
            ->set('summary', 'A test project summary')
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('project', [
            'name' => 'New Test Project',
            'slug' => 'new-test-project',
            'summary' => 'A test project summary',
            'approval_status' => ApprovalStatus::DRAFT->value,
            'project_type_id' => $this->projectType->id,
        ]);

        $this->assertDatabaseHas('membership', [
            'user_id' => $this->user->id,
            'role' => 'owner',
            'primary' => true,
        ]);
    }

    #[Test]
    public function test_summary_max_length()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->set('selectedType', $this->projectType->value)
            ->set('name', 'Test')
            ->set('slug', 'test-project')
            ->set('summary', str_repeat('a', 126))
            ->call('create')
            ->assertHasErrors(['summary']);
    }

    #[Test]
    public function test_name_max_length()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->set('selectedType', $this->projectType->value)
            ->set('name', str_repeat('a', 256))
            ->set('slug', 'test-project')
            ->set('summary', 'Test summary')
            ->call('create')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function test_project_types_are_loaded()
    {
        $projectType2 = ProjectType::factory()->create();

        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->assertViewHas('projectTypes');
    }

    #[Test]
    public function test_unverified_user_cannot_open_modal()
    {
        $unverifiedUser = User::factory()->unverified()->create();

        Livewire::actingAs($unverifiedUser)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->assertSet('isOpen', false);
    }

    #[Test]
    public function test_unverified_user_cannot_create_project()
    {
        $unverifiedUser = User::factory()->unverified()->create();

        Livewire::actingAs($unverifiedUser)
            ->test(ProjectCreateModal::class)
            ->set('selectedType', $this->projectType->value)
            ->set('name', 'Blocked Project')
            ->set('slug', 'blocked-project')
            ->set('summary', 'Blocked summary')
            ->call('create');

        $this->assertDatabaseMissing('project', [
            'name' => 'Blocked Project',
        ]);
    }

    #[Test]
    public function test_updated_slug_validates_immediately()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->set('slug', 'Invalid Slug!')
            ->assertHasErrors(['slug']);
    }

    #[Test]
    public function test_open_resets_form_values_and_validation()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open', $this->projectType->value)
            ->set('name', 'Temporary Name')
            ->set('slug', 'invalid slug with spaces')
            ->call('create')
            ->assertHasErrors(['slug'])
            ->call('open', $this->projectType->value)
            ->assertSet('name', '')
            ->assertSet('slug', '')
            ->assertSet('summary', '')
            ->assertHasNoErrors();
    }

    #[Test]
    public function test_close_resets_validation_errors()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open')
            ->call('create')
            ->assertHasErrors(['selectedType', 'name', 'slug', 'summary'])
            ->call('close')
            ->assertHasNoErrors();
    }

    #[Test]
    public function test_create_handles_service_failure_without_creating_project()
    {
        $mock = Mockery::mock(ProjectService::class);
        $mock->shouldReceive('generateSlug')->andReturn('new-test-project');
        $mock->shouldReceive('saveProject')->once()->andThrow(new \Exception('forced failure'));
        $this->app->instance(ProjectService::class, $mock);

        Livewire::actingAs($this->user)
            ->test(ProjectCreateModal::class)
            ->call('open', $this->projectType->value)
            ->set('selectedType', $this->projectType->value)
            ->set('name', 'New Test Project')
            ->set('slug', 'new-test-project')
            ->set('summary', 'A test project summary')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('project', [
            'name' => 'New Test Project',
            'slug' => 'new-test-project',
        ]);
    }
}
