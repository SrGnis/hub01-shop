<?php

namespace Tests\Feature\Project\Livewire;

use App\Enums\ApprovalStatus;
use App\Livewire\ProjectCreateModal;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
        Livewire::actingAsGuest()
            ->test(ProjectCreateModal::class)
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
}
