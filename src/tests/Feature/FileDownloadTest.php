<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FileDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectType $projectType;
    protected Project $project;
    protected ProjectVersion $version;
    protected UploadedFile $testFile;
    protected string $filePath;
    protected ProjectFile $projectFile;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();

        // Create project type
        $this->projectType = ProjectType::firstOrCreate([
            'value' => 'mod',
            'display_name' => 'Mod',
            'icon' => 'lucide-puzzle',
        ]);

        // Create project
        $projectName = 'Test Project';
        $this->project = Project::factory()->create([
            'name' => $projectName,
            'slug' => Str::slug($projectName),
            'project_type_id' => $this->projectType->id,
        ]);

        // Create project version
        $this->version = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
            'version' => '1.0.0',
            'name' => 'Test Version',
            'release_type' => 'release',
            'release_date' => now(),
            'downloads' => 0,
        ]);

        // Create test file
        $this->testFile = UploadedFile::fake()->create('test-mod.zip', 100);
        $this->filePath = $this->testFile->store('project-files');

        // Create a file for the version
        $this->projectFile = ProjectFile::factory()->create([
            'project_version_id' => $this->version->id,
            'name' => 'test-mod.zip',
            'path' => $this->filePath,
            'size' => $this->testFile->getSize()
        ]);
    }

    #[Test]
    public function file_download_route_works_correctly()
    {

        // Test the download route
        $response = $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $this->projectFile->name
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=test-mod.zip');
    }

    #[Test]
    public function file_download_increments_download_count()
    {

        // Initial download count should be 0
        $this->assertEquals(0, $this->version->downloads);

        // Download the file
        $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => $this->projectFile->name
        ]));

        // Refresh the model from the database
        $this->version->refresh();

        // Download count should be incremented
        $this->assertEquals(1, $this->version->downloads);
    }

    #[Test]
    public function file_download_returns_404_for_nonexistent_file()
    {
        // Test with a non-existent file name
        $response = $this->get(route('file.download', [
            'projectType' => $this->projectType,
            'project' => $this->project,
            'version' => $this->version->version,
            'file' => 'nonexistent-file.zip'
        ]));

        $response->assertStatus(404);
    }
}
