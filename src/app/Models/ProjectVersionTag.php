<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class ProjectVersionTag extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_version_tag';

    protected $fillable = [
        'name',
        'icon',
        'project_version_tag_group_id',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
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

    /**
     * Get the tag group for this tag
     */
    public function tagGroup(): BelongsTo
    {
        return $this->belongsTo(ProjectVersionTagGroup::class, 'project_version_tag_group_id');
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
}
