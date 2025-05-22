<?php

namespace App\Models;

use App\Enums\ReleaseType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ProjectVersion extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectVersionFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_version';

    protected $fillable = [
        'name',
        'version',
        'changelog',
        'release_type',
        'release_date',
        'downloads',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'version';
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updated(function ($projectVersion) {
            if ($projectVersion->isDirty('downloads')) {
                $projectVersion->project->clearDownloadsCache();
            }

            if ($projectVersion->isDirty('release_date') || $projectVersion->isDirty('release_type')) {
                $projectVersion->project->clearRecentReleaseDateCache();
            }

            $projectVersion->project->clearRecentVersionsCache();

            $projectVersion->clearTagGroupCaches();
        });

        static::created(function ($projectVersion) {

            $projectVersion->project->clearRecentReleaseDateCache();

            $projectVersion->project->clearRecentVersionsCache();

            $projectVersion->clearTagGroupCaches();
        });

        static::deleting(function ($projectVersion) {

            $projectVersion->project->clearRecentReleaseDateCache();

            $projectVersion->project->clearDownloadsCache();

            $projectVersion->project->clearRecentVersionsCache();
        });
    }

    /**
     * Get the project that owns the project version
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the files for the project version
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    /**
     * Get all dependencies of this project version
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(ProjectVersionDependency::class, 'project_version_id');
    }

    /**
     * Get all project versions that depend on this project version
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dependedOnBy(): HasMany
    {
        return $this->hasMany(ProjectVersionDependency::class, 'dependency_project_version_id');
    }

    /**
     * Get all project version dependencies that are required
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requiredDependencies(): HasMany
    {
        return $this->dependencies()->where('dependency_type', 'required');
    }

    /**
     * Get all project version dependencies that are optional
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function optionalDependencies(): HasMany
    {
        return $this->dependencies()->where('dependency_type', 'optional');
    }

    /**
     * Get all project version dependencies that are embedded
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function embeddedDependencies(): HasMany
    {
        return $this->dependencies()->where('dependency_type', 'embedded');
    }

    /**
     * Get the background color class attribute
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function bgColorClass(): Attribute
    {
        return Attribute::make(
            get: fn () => ReleaseType::fromString($this->release_type)->bgColorClass()
        );
    }

    /**
     * Get the display name attribute
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => ReleaseType::fromString($this->release_type)->displayName()
        );
    }

    /**
     * The tags that belong to the ProjectVersion
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectVersionTag::class,
            'project_version_project_version_tag',
            'project_version_id',
            'tag_id'
        );
    }

    /**
     * Clear the tags cache for this project version
     *
     * @return void
     */
    public function clearTagsCache(): void
    {
        Cache::forget('project_version_tags_' . $this->id);
    }

    /**
     * Clear the tag groups cache for this project type
     *
     * @return void
     */
    public function clearTagGroupCaches(): void
    {
        $projectType = $this->project->projectType;

        Cache::forget('project_tag_groups_by_type_' . $projectType->value);

        Cache::forget('project_version_tag_groups_by_type_' . $projectType->value);
    }
}
