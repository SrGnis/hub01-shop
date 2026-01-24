<?php

namespace Tests\Feature\Project\Livewire;

use App\Livewire\ProjectVersionForm;
use App\Models\Membership;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionTag;
use App\Models\ProjectVersionTagGroup;
use App\Models\User;
use App\Notifications\BrokenDependencyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectVersionFormTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;
    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();

        $this->projectType = ProjectType::factory()->create();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
    }

    // ========== Component Rendering Tests ==========

    #[Test]
    public function test_component_renders_for_new_version()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->assertOk()
            ->assertViewIs('livewire.project-version-form')
            ->assertSet('isEditing', false)
            ->assertSet('release_date', now()->format('Y-m-d'));
    }

    #[Test]
    public function test_component_renders_for_existing_version()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
            'name' => 'Test Version',
        ]);

        $file = $version->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/test.zip',
            'size' => 1024,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ])
            ->assertOk()
            ->assertSet('isEditing', true)
            ->assertSet('name', 'Test Version')
            ->assertSet('version_number', '1.0.0');
    }

    // ========== Authentication & Authorization Tests ==========

    #[Test]
    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('project.version.create', [
            'projectType' => $this->projectType,
            'project' => $this->project,
        ]))
            ->assertRedirect(route('verification.notice'));
    }

    #[Test]
    public function test_cannot_create_version_for_deactivated_project()
    {
        $this->project->update(['deactivated_at' => now()]);

        $this->actingAs($this->user)
            ->get(route('project.version.create', [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ]))
            ->assertRedirect(route('project.show', [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function test_cannot_edit_version_without_permission()
    {
        $otherUser = User::factory()->create();
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $this->actingAs($otherUser)
            ->get(route('project.version.edit', [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ]))
            ->assertRedirect(route('project.show', [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ]))
            ->assertSessionHas('error');
    }

    #[Test]
    public function test_owner_can_create_version()
    {
        $this->actingAs($this->user)
            ->get(route('project.version.create', [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ]))
            ->assertOk();
    }

    #[Test]
    public function test_member_can_create_version()
    {
        $member = User::factory()->create();

        $membership = new Membership([
            'role' => 'contributor',
            'primary' => false,
            'status' => 'active',
        ]);
        $membership->user()->associate($member);
        $membership->project()->associate($this->project);
        $membership->save();

        $this->actingAs($member)
            ->get(route('project.version.create', [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ]))
            ->assertOk();
    }

    // ========== Validation Tests ==========

    #[Test]
    public function test_validation_requires_name()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', '')
            ->set('files', [$file])
            ->call('save')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function test_validation_requires_version_number()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '')
            ->set('files', [$file])
            ->call('save')
            ->assertHasErrors(['version_number']);
    }

    #[Test]
    public function test_validation_requires_unique_version_number()
    {
        ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
        ]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->call('save')
            ->assertHasErrors(['version_number']);
    }

    #[Test]
    public function test_validation_allows_same_version_number_when_editing()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
        ]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ])
            ->set('name', 'Updated Version')
            ->set('version_number', '1.0.0')
            ->set('files', [$file])
            ->call('save')
            ->assertHasNoErrors(['version_number']);
    }

    #[Test]
    public function test_validation_requires_release_type()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', '')
            ->set('files', [$file])
            ->call('save')
            ->assertHasErrors(['release_type']);
    }

    #[Test]
    public function test_validation_release_type_must_be_valid()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'invalid')
            ->set('files', [$file])
            ->call('save')
            ->assertHasErrors(['release_type']);
    }

    #[Test]
    public function test_validation_requires_release_date()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_date', '')
            ->set('files', [$file])
            ->call('save')
            ->assertHasErrors(['release_date']);
    }

    #[Test]
    public function test_validation_requires_at_least_one_file_on_create()
    {
        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [])
            ->call('save')
            ->assertHasErrors(['files']);
    }

    #[Test]
    public function test_validation_allows_no_new_files_on_edit()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $version->files()->create([
            'name' => 'existing.zip',
            'path' => 'project-files/existing.zip',
            'size' => 1024,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ])
            ->set('name', 'Updated Version')
            ->set('files', [])
            ->call('save')
            ->assertHasNoErrors(['files']);
    }

    // TODO: fix this test
    // UploadedFile::fake() does not work properly and creates a 0 size file so the validation does not fail
    // #[Test]
    // public function test_validation_file_size_limit()
    // {
    //     // Create file larger than 100MB (102400 KB)
    //     $file = UploadedFile::fake()->create('large.zip', 102401);

    //     Livewire::actingAs($this->user)
    //         ->test(ProjectVersionForm::class, [
    //             'projectType' => $this->projectType,
    //             'project' => $this->project,
    //         ])
    //         ->set('name', 'Test Version')
    //         ->set('version_number', '1.0.0')
    //         ->set('release_type', 'release')
    //         ->set('release_date', now()->format('Y-m-d'))
    //         ->set('files', [$file])
    //         ->call('save')
    //         ->assertHasErrors();
    // }

    #[Test]
    public function test_validation_duplicate_file_names_in_upload()
    {
        $file1 = UploadedFile::fake()->create('duplicate.zip', 1024);
        $file2 = UploadedFile::fake()->create('duplicate.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file1, $file2])
            ->call('save')
            ->assertHasErrors();
    }

    #[Test]
    public function test_validation_duplicate_file_name_with_existing()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $version->files()->create([
            'name' => 'existing.zip',
            'path' => 'project-files/existing.zip',
            'size' => 1024,
        ]);

        $newFile = UploadedFile::fake()->create('existing.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ])
            ->set('files', [$newFile])
            ->call('save')
            ->assertHasErrors();
    }

    #[Test]
    public function test_validation_tags_must_belong_to_project_type()
    {
        $otherProjectType = ProjectType::factory()->create();
        $invalidTag = ProjectVersionTag::factory()->create();
        $invalidTag->projectTypes()->attach($otherProjectType);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->set('selectedTags', [$invalidTag->id])
            ->call('save')
            ->assertHasErrors(['selectedTags']);
    }

    #[Test]
    public function test_create_version_with_only_subtags()
    {
        $parentTag = ProjectVersionTag::factory()->create();
        $parentTag->projectTypes()->attach($this->projectType);

        $subTag = ProjectVersionTag::factory()->create(['parent_id' => $parentTag->id]);
        $subTag->projectTypes()->attach($this->projectType);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->set('selectedTags', [$subTag->id]) // Only select subtag
            ->call('save')
            ->assertHasNoErrors();

        $version = ProjectVersion::where('version', '1.0.0')->first();

        // Should have both the subtag and its parent tag
        $this->assertCount(2, $version->tags);
        $this->assertTrue($version->tags->contains('id', $subTag->id));
        $this->assertTrue($version->tags->contains('id', $parentTag->id));
    }

    #[Test]
    public function test_create_version_with_mixed_tags()
    {
        $parentTag1 = ProjectVersionTag::factory()->create();
        $parentTag1->projectTypes()->attach($this->projectType);

        $parentTag2 = ProjectVersionTag::factory()->create();
        $parentTag2->projectTypes()->attach($this->projectType);

        $subTag = ProjectVersionTag::factory()->create(['parent_id' => $parentTag1->id]);
        $subTag->projectTypes()->attach($this->projectType);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->set('selectedTags', [$parentTag2->id, $subTag->id]) // Mix: one parent, one subtag
            ->call('save')
            ->assertHasNoErrors();

        $version = ProjectVersion::where('version', '1.0.0')->first();

        // Should have 3 tags: parentTag2, subTag, and parentTag1 (auto-added)
        $this->assertCount(3, $version->tags);
        $this->assertTrue($version->tags->contains('id', $parentTag1->id));
        $this->assertTrue($version->tags->contains('id', $parentTag2->id));
        $this->assertTrue($version->tags->contains('id', $subTag->id));
    }

    #[Test]
    public function test_validate_version_subtag_parent_belongs_to_project_type()
    {
        $otherProjectType = ProjectType::factory()->create();

        // Parent tag belongs to OTHER project type
        $parentTag = ProjectVersionTag::factory()->create();
        $parentTag->projectTypes()->attach($otherProjectType);

        // Subtag (child of parent that doesn't belong to our project type)
        $subTag = ProjectVersionTag::factory()->create(['parent_id' => $parentTag->id]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->set('selectedTags', [$subTag->id])
            ->call('save')
            ->assertHasErrors(['selectedTags']); // Should fail because parent doesn't belong to project type
    }

    #[Test]
    public function test_invalid_version_subtag_with_wrong_parent()
    {
        $wrongParentTag = ProjectVersionTag::factory()->create(); // Not associated with project type

        $subTag = ProjectVersionTag::factory()->create(['parent_id' => $wrongParentTag->id]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->set('selectedTags', [$subTag->id])
            ->call('save')
            ->assertHasErrors(['selectedTags']);
    }

    #[Test]
    public function test_update_version_with_subtags()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $oldTag = ProjectVersionTag::factory()->create();
        $oldTag->projectTypes()->attach($this->projectType);
        $version->tags()->attach($oldTag);

        $parentTag = ProjectVersionTag::factory()->create();
        $parentTag->projectTypes()->attach($this->projectType);

        $subTag = ProjectVersionTag::factory()->create(['parent_id' => $parentTag->id]);
        $subTag->projectTypes()->attach($this->projectType);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ])
            ->set('files', [$file])
            ->set('selectedTags', [$subTag->id]) // Replace old tag with subtag
            ->call('save')
            ->assertHasNoErrors();

        $version->refresh();

        // Should have subtag and its parent, but not the old tag
        $this->assertCount(2, $version->tags);
        $this->assertTrue($version->tags->contains('id', $subTag->id));
        $this->assertTrue($version->tags->contains('id', $parentTag->id));
        $this->assertFalse($version->tags->contains('id', $oldTag->id));
    }


    // ========== File Upload & Management Tests ==========

    #[Test]
    public function test_upload_single_file()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->call('save')
            ->assertHasNoErrors();

        $version = ProjectVersion::where('version', '1.0.0')->first();
        $this->assertCount(1, $version->files);
        $this->assertEquals('test.zip', $version->files->first()->name);
    }

    #[Test]
    public function test_upload_multiple_files()
    {
        $file1 = UploadedFile::fake()->create('file1.zip', 1024);
        $file2 = UploadedFile::fake()->create('file2.zip', 2048);
        $file3 = UploadedFile::fake()->create('file3.zip', 3072);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file1, $file2, $file3])
            ->call('save')
            ->assertHasNoErrors();

        $version = ProjectVersion::where('version', '1.0.0')->first();
        $this->assertCount(3, $version->files);
    }

    #[Test]
    public function test_remove_new_file_before_save()
    {
        $file1 = UploadedFile::fake()->create('file1.zip', 1024);
        $file2 = UploadedFile::fake()->create('file2.zip', 2048);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('files', [$file1, $file2]);

        $this->assertCount(2, $component->get('files'));

        $component->call('removeNewFile', 0);

        $this->assertCount(1, $component->get('files'));
    }

    #[Test]
    public function test_remove_existing_file_when_editing()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $file = $version->files()->create([
            'name' => 'existing.zip',
            'path' => 'project-files/existing.zip',
            'size' => 1024,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ]);

        $component->call('removeExistingFile', $file->id);

        $existingFiles = $component->get('existingFiles');
        $this->assertTrue($existingFiles[0]['delete']);
    }

    // ========== Dependency Management Tests ==========

    #[Test]
    public function test_add_dependency()
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ]);

        $this->assertCount(0, $component->get('dependencies'));

        $component->call('addDependency');

        $dependencies = $component->get('dependencies');
        $this->assertCount(1, $dependencies);
        $this->assertEquals('required', $dependencies[0]['type']);
        $this->assertEquals('linked', $dependencies[0]['mode']);
    }

    #[Test]
    public function test_remove_dependency()
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->call('addDependency')
            ->call('addDependency');

        $this->assertCount(2, $component->get('dependencies'));

        $component->call('removeDependency', 0);

        $this->assertCount(1, $component->get('dependencies'));
    }

    #[Test]
    public function test_linked_dependency_with_project()
    {
        $dependencyProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
            'slug' => 'dependency-project',
        ]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->call('addDependency')
            ->set('dependencies.0.project_slug', 'dependency-project')
            ->set('dependencies.0.type', 'required')
            ->call('save')
            ->assertHasNoErrors();

        $version = ProjectVersion::where('version', '1.0.0')->first();
        $this->assertCount(1, $version->dependencies);
        $this->assertEquals($dependencyProject->id, $version->dependencies->first()->dependency_project_id);
    }

    #[Test]
    public function test_linked_dependency_with_specific_version()
    {
        $dependencyProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
            'slug' => 'dependency-project',
        ]);

        $dependencyVersion = ProjectVersion::factory()->create([
            'project_id' => $dependencyProject->id,
            'version' => '2.0.0',
        ]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->call('addDependency')
            ->set('dependencies.0.project_slug', 'dependency-project')
            ->set('dependencies.0.project_id', $dependencyProject->id)
            ->set('dependencies.0.has_specific_version', true)
            ->set('dependencies.0.version_id', $dependencyVersion->id)
            ->set('dependencies.0.type', 'required')
            ->call('save')
            ->assertHasNoErrors();

        $version = ProjectVersion::where('version', '1.0.0')->first();
        $this->assertEquals($dependencyVersion->id, $version->dependencies->first()->dependency_project_version_id);
    }

    #[Test]
    public function test_manual_dependency_without_version()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->call('addDependency')
            ->set('dependencies.0.mode', 'manual')
            ->set('dependencies.0.dependency_name', 'External Library')
            ->set('dependencies.0.type', 'optional')
            ->call('save')
            ->assertHasNoErrors();

        $version = ProjectVersion::where('version', '1.0.0')->first();
        $dependency = $version->dependencies->first();
        $this->assertEquals('External Library', $dependency->dependency_name);
        $this->assertEquals('Any', $dependency->dependency_version);
    }

    #[Test]
    public function test_manual_dependency_with_version()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->call('addDependency')
            ->set('dependencies.0.mode', 'manual')
            ->set('dependencies.0.dependency_name', 'External Library')
            ->set('dependencies.0.has_manual_version', true)
            ->set('dependencies.0.dependency_version', '2.0.0')
            ->set('dependencies.0.type', 'required')
            ->call('save')
            ->assertHasNoErrors();

        $version = ProjectVersion::where('version', '1.0.0')->first();
        $dependency = $version->dependencies->first();
        $this->assertEquals('External Library', $dependency->dependency_name);
        $this->assertEquals('2.0.0', $dependency->dependency_version);
    }

    #[Test]
    public function test_dependency_validation_linked_requires_project()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->call('addDependency')
            ->set('dependencies.0.mode', 'linked')
            ->set('dependencies.0.project_slug', '')
            ->call('save')
            ->assertHasErrors(['dependencies.0.project_id']);
    }

    #[Test]
    public function test_dependency_validation_manual_requires_name()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->call('addDependency')
            ->set('dependencies.0.mode', 'manual')
            ->set('dependencies.0.dependency_name', '')
            ->call('save')
            ->assertHasErrors(['dependencies.0.dependency_name']);
    }

    #[Test]
    public function test_switch_dependency_mode_clears_fields()
    {
        $dependencyProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
            'slug' => 'dependency-project',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->call('addDependency')
            ->set('dependencies.0.project_slug', 'dependency-project')
            ->set('dependencies.0.project_id', $dependencyProject->id);

        // Switch to manual mode
        $component->set('dependencies.0.mode', 'manual');

        $dependencies = $component->get('dependencies');
        $this->assertEquals('', $dependencies[0]['project_slug']);
        $this->assertNull($dependencies[0]['project_id']);
    }

    #[Test]
    public function test_validate_project_slug_finds_project()
    {
        $dependencyProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
            'slug' => 'valid-project',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->call('addDependency')
            ->set('dependencies.0.project_slug', 'valid-project');

        $dependencies = $component->get('dependencies');
        $this->assertEquals($dependencyProject->id, $dependencies[0]['project_id']);
    }

    #[Test]
    public function test_validate_project_slug_invalid_slug()
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->call('addDependency')
            ->set('dependencies.0.project_slug', 'nonexistent-project');

        $dependencies = $component->get('dependencies');
        $this->assertNull($dependencies[0]['project_id']);
    }

    #[Test]
    public function test_cannot_depend_on_self()
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->call('addDependency')
            ->set('dependencies.0.project_slug', $this->project->slug);

        $dependencies = $component->get('dependencies');
        $this->assertNull($dependencies[0]['project_id']);
    }

    // ========== Tag Management Tests ==========

    #[Test]
    public function test_select_tags()
    {
        $tag1 = ProjectVersionTag::factory()->create();
        $tag1->projectTypes()->attach($this->projectType);

        $tag2 = ProjectVersionTag::factory()->create();
        $tag2->projectTypes()->attach($this->projectType);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('files', [$file])
            ->set('selectedTags', [$tag1->id, $tag2->id])
            ->call('save')
            ->assertHasNoErrors();

        $version = ProjectVersion::where('version', '1.0.0')->first();
        $this->assertCount(2, $version->tags);
    }

    // ========== Save & Update Tests ==========

    #[Test]
    public function test_create_new_version()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('name', 'Test Version')
            ->set('version_number', '1.0.0')
            ->set('release_type', 'release')
            ->set('release_date', now()->format('Y-m-d'))
            ->set('changelog', 'Initial release')
            ->set('files', [$file])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('project_version', [
            'project_id' => $this->project->id,
            'name' => 'Test Version',
            'version' => '1.0.0',
            'release_type' => 'release',
        ]);
    }

    #[Test]
    public function test_update_existing_version()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
            'name' => 'Original Name',
        ]);

        $version->files()->create([
            'name' => 'existing.zip',
            'path' => 'project-files/existing.zip',
            'size' => 1024,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ])
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('project_version', [
            'id' => $version->id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function test_version_number_validation_on_update()
    {
        ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ])
            ->set('version_number', '1.0.0');

        // Should have error for duplicate
        $component->assertHasErrors(['version_number']);

        // Change to unique version
        $component->set('version_number', '2.0.0');
        $component->assertHasNoErrors(['version_number']);
    }

    // ========== Delete Version Tests ==========

    #[Test]
    public function test_delete_version_requires_confirmation()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ])
            ->set('deleteConfirmation', 'wrong-version')
            ->call('deleteVersion')
            ->assertHasErrors(['deleteConfirmation']);

        $this->assertDatabaseHas('project_version', ['id' => $version->id]);
    }

    #[Test]
    public function test_delete_version_successfully()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
        ]);

        $file = $version->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/test.zip',
            'size' => 1024,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, 'content');

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ])
            ->set('deleteConfirmation', '1.0.0')
            ->call('deleteVersion')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseMissing('project_version', ['id' => $version->id]);
        Storage::disk(ProjectFile::getDisk())->assertMissing($file->path);
    }

    #[Test]
    public function test_delete_version_notifies_dependent_projects()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
        ]);

        $dependentProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $dependentVersion = ProjectVersion::factory()->create([
            'project_id' => $dependentProject->id,
        ]);

        $dependentVersion->dependencies()->create([
            'dependency_project_version_id' => $version->id,
            'dependency_type' => 'required',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version_key' => $version->version,
            ])
            ->set('deleteConfirmation', '1.0.0')
            ->call('deleteVersion');

        Notification::assertSentTo($this->user, BrokenDependencyNotification::class);
    }

    #[Test]
    public function test_cannot_delete_when_not_editing()
    {
        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ]);

        $this->assertFalse($component->get('isEditing'));

        $component->call('deleteVersion');

        // Should do nothing
    }

    // ========== Helper Method Tests ==========

    #[Test]
    public function test_refresh_markdown_dummy_method()
    {
        $this->expectNotToPerformAssertions();

        $component = Livewire::actingAs($this->user)
            ->test(ProjectVersionForm::class, [
                'projectType' => $this->projectType,
                'project' => $this->project,
            ]);

        // Should not throw error
        $component->call('refreshMarkdown');

    }
}
