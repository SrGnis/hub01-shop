<?php

namespace Tests\Feature\Project\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionDailyDownload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileDownloadControllerTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $projectType;
    private User $user;
    private Project $project;
    private ProjectVersion $version;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();

        $this->projectType = ProjectType::factory()->create();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $this->version = ProjectVersion::factory()->withoutDailyDownloads()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
        ]);
    }

    #[Test]
    public function test_download_file_successfully()
    {
        $fileContent = 'test file content';
        $file = $this->version->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/test.zip',
            'size' => strlen($fileContent),
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, $fileContent);

        $response = $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $file->name,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/octet-stream');
        $response->assertDownload($file->name);
    }

    #[Test]
    public function test_download_increments_download_count()
    {
        $file = $this->version->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/test.zip',
            'size' => 1024,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, 'content');

        $initialDownloads = $this->version->downloads;

        $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $file->name,
        ]));

        $this->version->refresh();
        $this->assertEquals($initialDownloads + 1, $this->version->downloads);
    }

    #[Test]
    public function test_download_is_deduplicated_for_three_hours_but_still_served()
    {
        $file = $this->version->files()->create([
            'name' => 'dedupe.zip',
            'path' => 'project-files/dedupe.zip',
            'size' => 512,
        ]);

        $initialDownloads = $this->version->downloads;

        Storage::disk(ProjectFile::getDisk())->put($file->path, 'content');

        $route = route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $file->name,
        ]);

        $responseOne = $this->withHeader('User-Agent', 'Hub01-Test-Agent')->get($route);
        $responseTwo = $this->withHeader('User-Agent', 'Hub01-Test-Agent')->get($route);

        $responseOne->assertOk()->assertDownload($file->name);
        $responseTwo->assertOk()->assertDownload($file->name);

        $this->version->refresh();
        $this->assertEquals($initialDownloads + 1, $this->version->downloads);
    }

    #[Test]
    public function test_counted_download_increments_daily_download_row()
    {
        $file = $this->version->files()->create([
            'name' => 'daily.zip',
            'path' => 'project-files/daily.zip',
            'size' => 128,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, 'content');

        $this->withHeader('User-Agent', 'Hub01-Daily-Agent')
            ->get(route('file.download', [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version' => $this->version->version,
                'file' => $file->name,
            ]))
            ->assertOk();

        $this->assertDatabaseHas('project_version_daily_download', [
            'project_version_id' => $this->version->id,
            'date' => today()->toDateString(),
            'downloads' => 1,
        ]);

        $this->assertEquals(
            1,
            ProjectVersionDailyDownload::where('project_version_id', $this->version->id)
                ->whereDate('date', today())
                ->value('downloads')
        );
    }

    #[Test]
    public function test_download_returns_file_content()
    {
        $fileContent = 'specific test content';
        $file = $this->version->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/test.zip',
            'size' => strlen($fileContent),
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, $fileContent);

        $response = $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $file->name,
        ]));

        $this->assertEquals($fileContent, $response->streamedContent());
    }

    #[Test]
    public function test_cannot_download_from_deactivated_project()
    {
        $this->project->update(['deactivated_at' => now()]);

        $file = $this->version->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/test.zip',
            'size' => 1024,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, 'content');

        $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $file->name,
        ]))
            ->assertNotFound();
    }

    #[Test]
    public function test_cannot_download_nonexistent_version()
    {
        $file = $this->version->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/test.zip',
            'size' => 1024,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, 'content');

        $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => 'nonexistent-version',
            'file' => $file->name,
        ]))
            ->assertNotFound();
    }

    #[Test]
    public function test_cannot_download_version_from_wrong_project()
    {
        $otherProject = Project::factory()->owner($this->user)->create([
            'project_type_id' => $this->projectType->id,
        ]);

        $otherVersion = ProjectVersion::factory()->create([
            'project_id' => $otherProject->id,
            'version' => '2.0.0',
        ]);

        $file = $otherVersion->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/test.zip',
            'size' => 1024,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, 'content');

        // Try to download from wrong project
        $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $otherVersion->version,
            'file' => $file->name,
        ]))
            ->assertNotFound();
    }

    #[Test]
    public function test_cannot_download_nonexistent_file()
    {
        $initialDownloads = $this->version->downloads;

        $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => 'nonexistent.zip',
        ]))
            ->assertNotFound();

        $this->version->refresh();
        $this->assertEquals($initialDownloads, $this->version->downloads);
        $this->assertDatabaseMissing('project_version_daily_download', [
            'project_version_id' => $this->version->id,
            'date' => today()->toDateString(),
        ]);
    }

    #[Test]
    public function test_cannot_download_file_not_in_storage()
    {
        $file = $this->version->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/nonexistent.zip',
            'size' => 1024,
        ]);

        $initialDownloads = $this->version->downloads;

        // Don't put file in storage

        $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $file->name,
        ]))
            ->assertNotFound();

        $this->version->refresh();
        $this->assertEquals($initialDownloads, $this->version->downloads);
        $this->assertDatabaseMissing('project_version_daily_download', [
            'project_version_id' => $this->version->id,
            'date' => today()->toDateString(),
        ]);
    }

    #[Test]
    public function test_download_works_for_guest_users()
    {
        $file = $this->version->files()->create([
            'name' => 'test.zip',
            'path' => 'project-files/test.zip',
            'size' => 1024,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, 'content');

        // No authentication
        $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $file->name,
        ]))
            ->assertOk();
    }

    #[Test]
    public function test_download_multiple_files_increments_count_separately()
    {
        $file1 = $this->version->files()->create([
            'name' => 'file1.zip',
            'path' => 'project-files/file1.zip',
            'size' => 1024,
        ]);

        $file2 = $this->version->files()->create([
            'name' => 'file2.zip',
            'path' => 'project-files/file2.zip',
            'size' => 2048,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file1->path, 'content1');
        Storage::disk(ProjectFile::getDisk())->put($file2->path, 'content2');

        $initialDownloads = $this->version->downloads;

        // Download first file
        $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $file1->name,
        ]));

        $this->version->refresh();
        $this->assertEquals($initialDownloads + 1, $this->version->downloads);

        // Download second file
        $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $file2->name,
        ]));

        $this->version->refresh();
        $this->assertEquals($initialDownloads + 2, $this->version->downloads);
    }

    #[Test]
    public function test_download_from_ignored_user_agent_does_not_increment_download_count()
    {
        Config::set('download_stats.bad_user_agent_patterns', ['Hub01BadBot']);

        $file = $this->version->files()->create([
            'name' => 'ignored.zip',
            'path' => 'project-files/ignored.zip',
            'size' => 256,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, 'content');

        $initialDownloads = $this->version->downloads;

        $this->withHeader('User-Agent', 'Mozilla/5.0 Hub01BadBot/1.0')
            ->get(route('file.download', [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version' => $this->version->version,
                'file' => $file->name,
            ]))
            ->assertOk()
            ->assertDownload($file->name);

        $this->version->refresh();
        $this->assertEquals($initialDownloads, $this->version->downloads);

        $this->assertDatabaseMissing('project_version_daily_download', [
            'project_version_id' => $this->version->id,
            'date' => today()->toDateString(),
        ]);
    }

    #[Test]
    public function test_ignored_user_agent_matching_is_case_insensitive_and_handles_escaped_spaces()
    {
        Config::set('download_stats.bad_user_agent_patterns', ['Example\ Bot']);

        $file = $this->version->files()->create([
            'name' => 'escaped-space.zip',
            'path' => 'project-files/escaped-space.zip',
            'size' => 128,
        ]);

        Storage::disk(ProjectFile::getDisk())->put($file->path, 'content');

        $initialDownloads = $this->version->downloads;

        $this->withHeader('User-Agent', 'mozilla/5.0 (compatible; EXAMPLE BOT crawler)')
            ->get(route('file.download', [
                'projectType' => $this->projectType,
                'project' => $this->project,
                'version' => $this->version->version,
                'file' => $file->name,
            ]))
            ->assertOk();

        $this->version->refresh();
        $this->assertEquals($initialDownloads, $this->version->downloads);
    }
}
