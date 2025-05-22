<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectTagGroup;
use App\Models\ProjectType;
use App\Models\ProjectVersion;
use App\Models\ProjectVersionTag;
use App\Models\ProjectVersionTagGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectType $projectType;
    protected Project $project;
    protected ProjectVersion $projectVersion;
    protected ProjectTagGroup $projectTagGroup;
    protected ProjectTag $projectTag;
    protected ProjectVersionTagGroup $projectVersionTagGroup;
    protected ProjectVersionTag $projectVersionTag;

    public function setUp(): void
    {
        parent::setUp();

        $this->projectType = ProjectType::factory()->create([
            'value' => 'test_type',
            'display_name' => 'Test Type',
        ]);

        $this->projectTagGroup = ProjectTagGroup::factory()->create([
            'name' => 'Test Tag Group',
        ]);
        $this->projectTagGroup->projectTypes()->attach($this->projectType->id);

        $this->projectTag = ProjectTag::factory()->create([
            'name' => 'Test Tag',
            'project_tag_group_id' => $this->projectTagGroup->id,
        ]);
        $this->projectTag->projectTypes()->attach($this->projectType->id);

        $this->projectVersionTagGroup = ProjectVersionTagGroup::factory()->create([
            'name' => 'Test Version Tag Group',
        ]);
        $this->projectVersionTagGroup->projectTypes()->attach($this->projectType->id);

        $this->projectVersionTag = ProjectVersionTag::factory()->create([
            'name' => 'Test Version Tag',
            'project_version_tag_group_id' => $this->projectVersionTagGroup->id,
        ]);
        $this->projectVersionTag->projectTypes()->attach($this->projectType->id);

        $this->project = Project::factory()->create([
            'project_type_id' => $this->projectType->id,
        ]);
        $this->project->tags()->attach($this->projectTag->id);

        $this->projectVersion = ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);
        $this->projectVersion->tags()->attach($this->projectVersionTag->id);

        Cache::flush();
    }

    #[Test]
    public function test_project_tag_groups_are_cached(): void
    {
        $cacheKey = 'project_tag_groups_by_type_' . $this->projectType->value;

        $this->assertFalse(Cache::has($cacheKey));

        $tagGroups = ProjectTagGroup::whereHas('projectTypes', function ($query) {
            $query->where('project_type_id', $this->projectType->id);
        })->with(['tags' => function ($query) {
            $query->whereHas('projectTypes', function ($subQuery) {
                $subQuery->where('project_type_id', $this->projectType->id);
            });
        }])->get();

        Cache::put($cacheKey, $tagGroups, now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $cachedTagGroups = Cache::get($cacheKey);
        $this->assertEquals($tagGroups->count(), $cachedTagGroups->count());
        $this->assertEquals($tagGroups->first()->id, $cachedTagGroups->first()->id);
    }

    #[Test]
    public function test_project_tag_groups_cache_is_invalidated_when_tag_group_is_updated(): void
    {
        $cacheKey = 'project_tag_groups_by_type_' . $this->projectType->value;

        Cache::put($cacheKey, 'test-value', now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $this->projectTagGroup->name = 'Updated Tag Group';
        $this->projectTagGroup->save();

        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function test_project_tag_groups_cache_is_invalidated_when_tag_group_is_deleted(): void
    {
        $cacheKey = 'project_tag_groups_by_type_' . $this->projectType->value;

        Cache::put($cacheKey, 'test-value', now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $this->projectTagGroup->forceDelete();

        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function test_project_version_tag_groups_are_cached(): void
    {
        $cacheKey = 'project_version_tag_groups_by_type_' . $this->projectType->value;

        $this->assertFalse(Cache::has($cacheKey));

        $tagGroups = ProjectVersionTagGroup::whereHas('projectTypes', function ($query) {
            $query->where('project_type_id', $this->projectType->id);
        })->with(['tags' => function ($query) {
            $query->whereHas('projectTypes', function ($subQuery) {
                $subQuery->where('project_type_id', $this->projectType->id);
            });
        }])->get();

        Cache::put($cacheKey, $tagGroups, now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $cachedTagGroups = Cache::get($cacheKey);
        $this->assertEquals($tagGroups->count(), $cachedTagGroups->count());
        $this->assertEquals($tagGroups->first()->id, $cachedTagGroups->first()->id);
    }

    #[Test]
    public function test_project_version_tag_groups_cache_is_invalidated_when_tag_group_is_updated(): void
    {
        $cacheKey = 'project_version_tag_groups_by_type_' . $this->projectType->value;

        Cache::put($cacheKey, 'test-value', now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $this->projectVersionTagGroup->name = 'Updated Version Tag Group';
        $this->projectVersionTagGroup->save();

        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function test_project_version_tag_groups_cache_is_invalidated_when_tag_group_is_deleted(): void
    {
        $cacheKey = 'project_version_tag_groups_by_type_' . $this->projectType->value;

        Cache::put($cacheKey, 'test-value', now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $this->projectVersionTagGroup->forceDelete();

        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function test_project_version_tags_are_cached(): void
    {
        $cacheKey = 'project_version_tags_by_type_' . $this->projectType->value;

        $this->assertFalse(Cache::has($cacheKey));

        $tags = ProjectVersionTag::whereHas('projectTypes', function ($query) {
            $query->where('project_type_id', $this->projectType->id);
        })->with('tagGroup')->get();

        Cache::put($cacheKey, $tags, now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $cachedTags = Cache::get($cacheKey);
        $this->assertEquals($tags->count(), $cachedTags->count());
        $this->assertEquals($tags->first()->id, $cachedTags->first()->id);
    }

    #[Test]
    public function test_project_version_tags_cache_is_invalidated_when_tag_is_updated(): void
    {
        $cacheKey = 'project_version_tags_by_type_' . $this->projectType->value;

        Cache::put($cacheKey, 'test-value', now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $this->projectVersionTag->name = 'Updated Version Tag';
        $this->projectVersionTag->save();

        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function test_project_version_tags_cache_is_invalidated_when_tag_is_deleted(): void
    {
        $cacheKey = 'project_version_tags_by_type_' . $this->projectType->value;

        Cache::put($cacheKey, 'test-value', now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $this->projectVersionTag->forceDelete();

        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function test_project_version_tags_per_version_are_cached(): void
    {
        $cacheKey = 'project_version_tags_' . $this->projectVersion->id;

        $this->assertFalse(Cache::has($cacheKey));

        Cache::put($cacheKey, $this->projectVersion->tags, now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $cachedTags = Cache::get($cacheKey);
        $this->assertEquals($this->projectVersion->tags->count(), $cachedTags->count());
        $this->assertEquals($this->projectVersion->tags->first()->id, $cachedTags->first()->id);
    }

    #[Test]
    public function test_project_version_tags_per_version_cache_is_invalidated_when_tag_is_updated(): void
    {
        $cacheKey = 'project_version_tags_' . $this->projectVersion->id;

        Cache::put($cacheKey, 'test-value', now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        $this->projectVersionTag->name = 'Updated Version Tag';
        $this->projectVersionTag->save();

        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function test_project_tag_groups_cache_should_be_invalidated_when_project_version_is_created(): void
    {
        $cacheKey = 'project_tag_groups_by_type_' . $this->projectType->value;

        Cache::put($cacheKey, 'test-value', now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $this->assertFalse(Cache::has($cacheKey), 'Cache should be invalidated when a project version is created');
    }

    #[Test]
    public function test_project_version_tag_groups_cache_should_be_invalidated_when_project_version_is_created(): void
    {
        $cacheKey = 'project_version_tag_groups_by_type_' . $this->projectType->value;

        Cache::put($cacheKey, 'test-value', now()->addHours(24));

        $this->assertTrue(Cache::has($cacheKey));

        ProjectVersion::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $this->assertFalse(Cache::has($cacheKey), 'Cache should be invalidated when a project version is created');
    }
}
