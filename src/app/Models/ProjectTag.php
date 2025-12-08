<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin IdeHelperProjectTag
 */
class ProjectTag extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_tag';

    protected $fillable = [
        'name',
        'icon',
        'project_tag_group_id',
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
}
