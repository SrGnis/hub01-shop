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
 * @mixin IdeHelperProjectTag
 */
class ProjectTag extends Model
{
    use HasFactory, HasUniqueSlug;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_tag';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'project_tag_group_id',
        'parent_id',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // Clear the tags cache when a tag is created, updated, or deleted
        static::saved(function ($tag) {
            foreach (ProjectType::all() as $projectType) {
                Cache::forget('project_tag_groups_by_type_'.$projectType->value);
            }
        });

        static::deleting(function ($tag) {
            foreach (ProjectType::all() as $projectType) {
                Cache::forget('project_tag_groups_by_type_'.$projectType->value);
            }
        });
    }

    /**
     * Get the tag group for this tag
     */
    public function tagGroup(): BelongsTo
    {
        return $this->belongsTo(ProjectTagGroup::class, 'project_tag_group_id');
    }

    /**
     * Get the parent tag (main tag) for this tag
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectTag::class, 'parent_id');
    }

    /**
     * Get the sub-tags (children) for this tag
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProjectTag::class, 'parent_id');
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
        return $this->belongsTo(ProjectTag::class, 'parent_id');
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
     * Get the hasSubTags attribute
     */
    public function gethasSubTagsAttribute(){
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
     * The projects that belong to the ProjectTag
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'project_project_tag',
            'tag_id',
            'project_id'
        );
    }

    /**
     * The project types that belong to the ProjectTag
     */
    public function projectTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectType::class,
            'project_tag_project_type',
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
