<?php

namespace App\Models;

use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin IdeHelperProjectVersionTag
 */
class ProjectVersionTag extends Model
{
    use HasFactory, HasUniqueSlug;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_version_tag';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'display_priority',
        'project_version_tag_group_id',
        'parent_id',
    ];

    protected $casts = [
        'display_priority' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('display_priority_order', function (Builder $builder) {
            $builder
                ->orderByDesc('display_priority')
                ->orderBy('slug');
        });

        static::saved(function ($tag) {
            foreach (ProjectType::all() as $projectType) {
                Cache::forget('project_version_tags_by_type_'.$projectType->value);
            }

            foreach ($tag->projectVersions as $projectVersion) {
                $projectVersion->clearTagsCache();
            }
        });

        static::deleting(function ($tag) {
            foreach (ProjectType::all() as $projectType) {
                Cache::forget('project_version_tags_by_type_'.$projectType->value);
            }

            foreach ($tag->projectVersions as $projectVersion) {
                $projectVersion->clearTagsCache();
            }
        });
    }

    public function generateSlug($model): string
    {
        return static::createSlug($model->name, $model->mainTag?->slug);
    }

    /**
     * Get the tag group for this tag
     */
    public function tagGroup(): BelongsTo
    {
        return $this->belongsTo(ProjectVersionTagGroup::class, 'project_version_tag_group_id');
    }

    /**
     * Get the parent tag (main tag) for this tag
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectVersionTag::class, 'parent_id');
    }

    /**
     * Get the sub-tags (children) for this tag
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProjectVersionTag::class, 'parent_id');
    }

    /**
     * Get the sub-tags alias for children relationship
     */
    public function subTags(): HasMany
    {
        return $this->children();
    }

    /**
     * Get the main tag (root parent) for this tag
     */
    public function mainTag(): BelongsTo
    {
        return $this->belongsTo(ProjectVersionTag::class, 'parent_id');
    }

    /**
     * Check if this tag is a sub-tag
     */
    public function isSubTag(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Check if this tag has sub-tags
     */
    public function hasSubTags(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the hasSubTags attribute for MaryUI expandable-condition
     */
    public function getHasSubTagsAttribute(): bool
    {
        return $this->hasSubTags();
    }

    /**
     * Get sub-tag count
     */
    public function subTagCount(): int
    {
        return $this->children()->count();
    }

    /**
     * The project versions that belong to the ProjectVersionTag
     */
    public function projectVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectVersion::class,
            'project_version_project_version_tag',
            'tag_id',
            'project_version_id'
        );
    }

    /**
     * The project types that belong to the ProjectVersionTag
     */
    public function projectTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectType::class,
            'project_version_tag_project_type',
            'tag_id',
            'project_type_id'
        );
    }

    /**
     * Scope to get only main tags (tags without a parent)
     */
    #[Scope]
    protected function onlyMain(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * Scope to get only sub-tags (tags with a parent)
     */
    #[Scope]
    protected function onlySub(Builder $query): void
    {
        $query->whereNotNull('parent_id');
    }

    /**
     * Get all parent tag IDs (for nested hierarchy support)
     */
    public function getParentTagIds(): array
    {
        $ids = [];
        $current = $this->parent;

        while ($current) {
            $ids[] = $current->id;
            $current = $current->parent;
        }

        return $ids;
    }
}
