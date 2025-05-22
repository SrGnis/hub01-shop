<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ProjectType extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_type';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'value',
        'display_name',
        'icon',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'value';
    }

    /**
     * Get the projects for this project type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * The tags that belong to the ProjectType
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projectTags(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectTag::class,
            'project_tag_project_type',
            'project_type_id',
            'tag_id'
        );
    }

    /**
     * The tag groups that belong to the ProjectType
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projectTagGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectTagGroup::class,
            'project_tag_group_project_type',
            'project_type_id',
            'tag_group_id'
        );
    }

    /**
     * The version tags that belong to the ProjectType
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projectVersionTags(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectVersionTag::class,
            'project_version_tag_project_type',
            'project_type_id',
            'tag_id'
        );
    }

    /**
     * The version tag groups that belong to the ProjectType
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projectVersionTagGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectVersionTagGroup::class,
            'project_version_tag_group_project_type',
            'project_type_id',
            'tag_group_id'
        );
    }

    /**
     * Get the pluralized display name for this project type.
     *
     * @return string
     */
    public function pluralizedDisplayName(): string
    {
        return $this->display_name . 's';
    }
}
