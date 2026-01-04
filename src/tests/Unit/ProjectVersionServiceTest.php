<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionTag;
use App\Models\ProjectVersionTagGroup;
use App\Models\User;
use App\Notifications\BrokenDependencyNotification;
use App\Services\ProjectVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectVersionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectVersionService $projectVersionService;
    private ProjectType $projectType;
    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();

        $this->projectVersionService = $this->app->make(ProjectVersionService::class);
        $this->projectType = ProjectType::factory()->create();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id
        ]);
    }

    #[Test]
    public function test_create_new_version()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        $versionData = [
            'name' => 'Test Version',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->format('Y-m-d'),
            'changelog' => 'Initial release',
        ];

        $projectVersion = $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            [],
            []
        );

        $this->assertInstanceOf(ProjectVersion::class, $projectVersion);
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
        ]);

        $file = UploadedFile::fake()->create('updated.zip', 1024);

        $versionData = [
            'name' => 'Updated Version',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->format('Y-m-d'),
            'changelog' => 'Updated release',
        ];

        $updatedVersion = $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            [],
            [],
            $version
        );

        $this->assertEquals($version->id, $updatedVersion->id);
        $this->assertDatabaseHas('project_version', [
            'id' => $version->id,
            'name' => 'Updated Version',
        ]);
    }

    #[Test]
    public function test_upload_new_files()
    {
        $file1 = UploadedFile::fake()->create('file1.zip', 1024);
        $file2 = UploadedFile::fake()->create('file2.zip', 2048);

        $versionData = [
            'name' => 'Test Version',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->format('Y-m-d'),
            'changelog' => 'Test',
        ];

        $projectVersion = $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file1, $file2],
            [],
            [],
            []
        );

        $this->assertCount(2, $projectVersion->files);
        $this->assertDatabaseHas('project_file', [
            'project_version_id' => $projectVersion->id,
            'name' => 'file1.zip',
        ]);
        $this->assertDatabaseHas('project_file', [
            'project_version_id' => $projectVersion->id,
            'name' => 'file2.zip',
        ]);
    }

    #[Test]
    public function test_delete_marked_files_when_editing()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $file1 = $version->files()->create([
            'name' => 'file1.zip',
            'path' => 'project-files/file1.zip',
            'size' => 1024,
        ]);

        $file2 = $version->files()->create([
            'name' => 'file2.zip',
            'path' => 'project-files/file2.zip',
            'size' => 2048,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file1->path, 'content1');
        Storage::disk(ProjectFile::getDisk())->put($file2->path, 'content2');

        $existingFiles = [
            ['id' => $file1->id, 'name' => 'file1.zip', 'delete' => true],
            ['id' => $file2->id, 'name' => 'file2.zip', 'delete' => false],
        ];

        $newFile = UploadedFile::fake()->create('file3.zip', 1024);

        $versionData = [
            'name' => $version->name,
            'version' => $version->version,
            'release_type' => $version->release_type->value,
            'release_date' => $version->release_date->format('Y-m-d'),
            'changelog' => $version->changelog,
        ];

        $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$newFile],
            $existingFiles,
            [],
            [],
            $version
        );

        $this->assertDatabaseMissing('project_file', ['id' => $file1->id]);
        $this->assertDatabaseHas('project_file', ['id' => $file2->id]);
        Storage::disk(ProjectFile::getDisk())->assertMissing($file1->path);
    }

    #[Test]
    public function test_prevent_duplicate_file_names()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already exists');

        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $version->files()->create([
            'name' => 'duplicate.zip',
            'path' => 'project-files/duplicate.zip',
            'size' => 1024,
        ]);

        $existingFiles = [
            ['id' => $version->files->first()->id, 'name' => 'duplicate.zip', 'delete' => false],
        ];

        $newFile = UploadedFile::fake()->create('duplicate.zip', 1024);

        $versionData = [
            'name' => $version->name,
            'version' => $version->version,
            'release_type' => $version->release_type->value,
            'release_date' => $version->release_date->format('Y-m-d'),
            'changelog' => $version->changelog,
        ];

        $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$newFile],
            $existingFiles,
            [],
            [],
            $version
        );
    }

    #[Test]
    public function test_save_linked_dependency_to_project()
    {
        $dependencyProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        $versionData = [
            'name' => 'Test Version',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->format('Y-m-d'),
            'changelog' => 'Test',
        ];

        $dependencies = [
            [
                'type' => 'required',
                'mode' => 'linked',
                'project_id' => $dependencyProject->id,
                'has_specific_version' => false,
                'version_id' => null,
                'dependency_name' => '',
                'dependency_version' => '',
            ],
        ];

        $projectVersion = $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            $dependencies,
            []
        );

        $this->assertDatabaseHas('project_version_dependency', [
            'project_version_id' => $projectVersion->id,
            'dependency_project_id' => $dependencyProject->id,
            'dependency_project_version_id' => null,
            'dependency_type' => 'required',
        ]);
    }

    #[Test]
    public function test_save_linked_dependency_to_specific_version()
    {
        $dependencyProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $dependencyVersion = ProjectVersion::factory()->create([
            'project_id' => $dependencyProject->id,
        ]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        $versionData = [
            'name' => 'Test Version',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->format('Y-m-d'),
            'changelog' => 'Test',
        ];

        $dependencies = [
            [
                'type' => 'required',
                'mode' => 'linked',
                'project_id' => $dependencyProject->id,
                'has_specific_version' => true,
                'version_id' => $dependencyVersion->id,
                'dependency_name' => '',
                'dependency_version' => '',
            ],
        ];

        $projectVersion = $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            $dependencies,
            []
        );

        $this->assertDatabaseHas('project_version_dependency', [
            'project_version_id' => $projectVersion->id,
            'dependency_project_id' => null,
            'dependency_project_version_id' => $dependencyVersion->id,
            'dependency_type' => 'required',
        ]);
    }

    #[Test]
    public function test_save_manual_dependency_without_version()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        $versionData = [
            'name' => 'Test Version',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->format('Y-m-d'),
            'changelog' => 'Test',
        ];

        $dependencies = [
            [
                'type' => 'optional',
                'mode' => 'manual',
                'project_id' => null,
                'has_specific_version' => false,
                'version_id' => null,
                'dependency_name' => 'External Library',
                'dependency_version' => '',
                'has_manual_version' => false,
            ],
        ];

        $projectVersion = $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            $dependencies,
            []
        );

        $this->assertDatabaseHas('project_version_dependency', [
            'project_version_id' => $projectVersion->id,
            'dependency_project_id' => null,
            'dependency_project_version_id' => null,
            'dependency_type' => 'optional',
            'dependency_name' => 'External Library',
            'dependency_version' => 'Any',
        ]);
    }

    #[Test]
    public function test_save_manual_dependency_with_version()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        $versionData = [
            'name' => 'Test Version',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->format('Y-m-d'),
            'changelog' => 'Test',
        ];

        $dependencies = [
            [
                'type' => 'required',
                'mode' => 'manual',
                'project_id' => null,
                'has_specific_version' => false,
                'version_id' => null,
                'dependency_name' => 'External Library',
                'dependency_version' => '2.0.0',
                'has_manual_version' => true,
            ],
        ];

        $projectVersion = $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            $dependencies,
            []
        );

        $this->assertDatabaseHas('project_version_dependency', [
            'project_version_id' => $projectVersion->id,
            'dependency_name' => 'External Library',
            'dependency_version' => '2.0.0',
        ]);
    }

    #[Test]
    public function test_save_dependency_with_type()
    {
        $dependencyProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        $versionData = [
            'name' => 'Test Version',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->format('Y-m-d'),
            'changelog' => 'Test',
        ];

        $dependencies = [
            [
                'type' => 'embedded',
                'mode' => 'linked',
                'project_id' => $dependencyProject->id,
                'has_specific_version' => false,
                'version_id' => null,
                'dependency_name' => '',
                'dependency_version' => '',
            ],
        ];

        $projectVersion = $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            $dependencies,
            []
        );

        $this->assertDatabaseHas('project_version_dependency', [
            'project_version_id' => $projectVersion->id,
            'dependency_type' => 'embedded',
        ]);
    }

    #[Test]
    public function test_delete_old_dependencies_when_updating()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $oldDependency = $version->dependencies()->create([
            'dependency_type' => 'required',
            'dependency_name' => 'Old Dependency',
            'dependency_version' => 'Any',
        ]);

        $dependencyProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        $versionData = [
            'name' => $version->name,
            'version' => $version->version,
            'release_type' => $version->release_type->value,
            'release_date' => $version->release_date->format('Y-m-d'),
            'changelog' => $version->changelog,
        ];

        $dependencies = [
            [
                'type' => 'required',
                'mode' => 'linked',
                'project_id' => $dependencyProject->id,
                'has_specific_version' => false,
                'version_id' => null,
                'dependency_name' => '',
                'dependency_version' => '',
            ],
        ];

        $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            $dependencies,
            [],
            $version
        );

        $this->assertDatabaseMissing('project_version_dependency', [
            'id' => $oldDependency->id,
        ]);
        $this->assertDatabaseHas('project_version_dependency', [
            'project_version_id' => $version->id,
            'dependency_project_id' => $dependencyProject->id,
        ]);
    }

    #[Test]
    public function test_attach_tags_on_create()
    {
        $tag1 = ProjectVersionTag::factory()->create();
        $tag1->projectTypes()->attach($this->projectType);

        $tag2 = ProjectVersionTag::factory()->create();
        $tag2->projectTypes()->attach($this->projectType);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        $versionData = [
            'name' => 'Test Version',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->format('Y-m-d'),
            'changelog' => 'Test',
        ];

        $projectVersion = $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            [],
            [$tag1->id, $tag2->id]
        );

        $this->assertCount(2, $projectVersion->tags);
        $this->assertTrue($projectVersion->tags->contains($tag1));
        $this->assertTrue($projectVersion->tags->contains($tag2));
    }

    #[Test]
    public function test_sync_tags_on_update()
    {
        $tag1 = ProjectVersionTag::factory()->create();
        $tag1->projectTypes()->attach($this->projectType);

        $tag2 = ProjectVersionTag::factory()->create();
        $tag2->projectTypes()->attach($this->projectType);

        $tag3 = ProjectVersionTag::factory()->create();
        $tag3->projectTypes()->attach($this->projectType);

        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $version->tags()->attach([$tag1->id, $tag2->id]);

        $file = UploadedFile::fake()->create('test.zip', 1024);

        $versionData = [
            'name' => $version->name,
            'version' => $version->version,
            'release_type' => $version->release_type->value,
            'release_date' => $version->release_date->format('Y-m-d'),
            'changelog' => $version->changelog,
        ];

        $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            [],
            [$tag2->id, $tag3->id],
            $version
        );

        $version->refresh();
        $this->assertCount(2, $version->tags);
        $this->assertFalse($version->tags->contains($tag1));
        $this->assertTrue($version->tags->contains($tag2));
        $this->assertTrue($version->tags->contains($tag3));
    }

    #[Test]
    public function test_save_version_with_no_tags()
    {
        $file = UploadedFile::fake()->create('test.zip', 1024);

        $versionData = [
            'name' => 'Test Version',
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->format('Y-m-d'),
            'changelog' => 'Test',
        ];

        $projectVersion = $this->projectVersionService->saveVersion(
            $this->project,
            $versionData,
            [$file],
            [],
            [],
            []
        );

        $this->assertCount(0, $projectVersion->tags);
    }

    #[Test]
    public function test_delete_version_removes_files()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $file1 = $version->files()->create([
            'name' => 'file1.zip',
            'path' => 'project-files/file1.zip',
            'size' => 1024,
        ]);

        $file2 = $version->files()->create([
            'name' => 'file2.zip',
            'path' => 'project-files/file2.zip',
            'size' => 2048,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file1->path, 'content1');
        Storage::disk(ProjectFile::getDisk())->put($file2->path, 'content2');

        Auth::login($this->user);
        $this->projectVersionService->deleteVersion($version, $this->project);

        $this->assertDatabaseMissing('project_file', ['id' => $file1->id]);
        $this->assertDatabaseMissing('project_file', ['id' => $file2->id]);
        Storage::disk(ProjectFile::getDisk())->assertMissing($file1->path);
        Storage::disk(ProjectFile::getDisk())->assertMissing($file2->path);
    }

    #[Test]
    public function test_delete_version_removes_dependencies()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $dependency = $version->dependencies()->create([
            'dependency_type' => 'required',
            'dependency_name' => 'Test Dependency',
            'dependency_version' => 'Any',
        ]);

        Auth::login($this->user);
        $this->projectVersionService->deleteVersion($version, $this->project);

        $this->assertDatabaseMissing('project_version_dependency', [
            'id' => $dependency->id,
        ]);
    }

    #[Test]
    public function test_delete_version_notifies_dependent_projects()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
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

        Auth::login($this->user);
        $this->projectVersionService->deleteVersion($version, $this->project);

        Notification::assertSentTo(
            $this->user,
            BrokenDependencyNotification::class
        );
    }

    #[Test]
    public function test_delete_version_groups_notifications_by_project()
    {
        $version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $dependentProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $dependentVersion1 = ProjectVersion::factory()->create([
            'project_id' => $dependentProject->id,
            'version' => '1.0.0',
        ]);

        $dependentVersion2 = ProjectVersion::factory()->create([
            'project_id' => $dependentProject->id,
            'version' => '2.0.0',
        ]);

        $dependentVersion1->dependencies()->create([
            'dependency_project_version_id' => $version->id,
            'dependency_type' => 'required',
        ]);

        $dependentVersion2->dependencies()->create([
            'dependency_project_version_id' => $version->id,
            'dependency_type' => 'required',
        ]);

        Auth::login($this->user);
        $this->projectVersionService->deleteVersion($version, $this->project);

        // Should send 2 notifications (one per dependent version)
        Notification::assertSentTo($this->user, BrokenDependencyNotification::class, 2);
    }

    #[Test]
    public function test_get_available_tags_for_project_type()
    {
        $tag1 = ProjectVersionTag::factory()->create();
        $tag1->projectTypes()->attach($this->projectType);

        $tag2 = ProjectVersionTag::factory()->create();
        $tag2->projectTypes()->attach($this->projectType);

        $otherProjectType = ProjectType::factory()->create();
        $tag3 = ProjectVersionTag::factory()->create();
        $tag3->projectTypes()->attach($otherProjectType);

        $tags = $this->projectVersionService->getAvailableTags($this->projectType);

        $this->assertCount(2, $tags);
        $this->assertTrue($tags->contains($tag1));
        $this->assertTrue($tags->contains($tag2));
        $this->assertFalse($tags->contains($tag3));
    }

    #[Test]
    public function test_get_available_tag_groups_for_project_type()
    {
        $tagGroup1 = ProjectVersionTagGroup::factory()->create();
        $tagGroup1->projectTypes()->attach($this->projectType);

        $tagGroup2 = ProjectVersionTagGroup::factory()->create();
        $tagGroup2->projectTypes()->attach($this->projectType);

        $otherProjectType = ProjectType::factory()->create();
        $tagGroup3 = ProjectVersionTagGroup::factory()->create();
        $tagGroup3->projectTypes()->attach($otherProjectType);

        $tagGroups = $this->projectVersionService->getAvailableTagGroups($this->projectType);

        $this->assertCount(2, $tagGroups);
        $this->assertTrue($tagGroups->contains($tagGroup1));
        $this->assertTrue($tagGroups->contains($tagGroup2));
        $this->assertFalse($tagGroups->contains($tagGroup3));
    }

    #[Test]
    public function test_get_version_options_for_project()
    {
        $version1 = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
            'release_type' => 'release',
            'release_date' => now()->subDays(10),
        ]);

        $version2 = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '2.0.0',
            'release_type' => 'beta',
            'release_date' => now()->subDays(5),
        ]);

        $version3 = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '3.0.0',
            'release_type' => 'alpha',
            'release_date' => now(),
        ]);

        $options = $this->projectVersionService->getVersionOptions($this->project->id);

        $this->assertCount(3, $options);
        // Should be ordered by release_date descending
        $this->assertEquals($version3->id, $options[0]['id']);
        $this->assertEquals($version2->id, $options[1]['id']);
        $this->assertEquals($version1->id, $options[2]['id']);

        // Check format
        $this->assertStringContainsString('3.0.0', $options[0]['name']);
        $this->assertStringContainsString('alpha', $options[0]['name']);
    }

    #[Test]
    public function test_cache_invalidation()
    {
        $tag = ProjectVersionTag::factory()->create();
        $tag->projectTypes()->attach($this->projectType);

        // First call should cache
        $tags1 = $this->projectVersionService->getAvailableTags($this->projectType);
        $this->assertCount(1, $tags1);

        // Add another tag
        $tag2 = ProjectVersionTag::factory()->create();
        $tag2->projectTypes()->attach($this->projectType);

        // Clear cache
        $cacheKey = 'project_version_tags_by_type_' . $this->projectType->value;
        Cache::forget($cacheKey);

        // Should get fresh data
        $tags2 = $this->projectVersionService->getAvailableTags($this->projectType);
        $this->assertCount(2, $tags2);
    }
}
