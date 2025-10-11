<?php

namespace App\Models;

use App\Enums\DependencyType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ProjectVersionDependency extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectVersionDependencyFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_version_dependency';

    protected $fillable = [
        'project_version_id',
        'dependency_project_version_id',
        'dependency_project_id',
        'dependency_type',
        'dependency_name',
        'dependency_version',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (ProjectVersionDependency $dependency) {
            if ($dependency->dependency_project_version_id === null && $dependency->dependency_project_id === null) {
                if (empty($dependency->dependency_name)) {
                    throw ValidationException::withMessages([
                        'dependency_name' => 'When not linking to a project or version, a dependency name must be provided.',
                    ]);
                }
            } elseif ($dependency->dependency_project_version_id !== null && $dependency->dependency_project_id !== null) {
                throw ValidationException::withMessages([
                    'dependency' => 'A dependency cannot reference both a specific project version and a general project.',
                ]);
            }

            if ($dependency->dependency_project_version_id) {
                $version = ProjectVersion::find($dependency->dependency_project_version_id);
                if ($version && $version->project) {
                    $dependency->dependency_name = $version->project->name;
                    $dependency->dependency_version = $version->version;
                }
            } elseif ($dependency->dependency_project_id) {
                $project = Project::find($dependency->dependency_project_id);
                if ($project) {
                    $dependency->dependency_name = $project->name;
                    $dependency->dependency_version = 'Any';
                }
            }

            if (empty($dependency->dependency_version) && ! empty($dependency->dependency_name)) {
                $dependency->dependency_version = 'Any';
            }
        });
    }

    /**
     * Get the project version that has this dependency
     */
    public function projectVersion(): BelongsTo
    {
        return $this->belongsTo(ProjectVersion::class, 'project_version_id');
    }

    /**
     * Get the specific project version that is depended on
     */
    public function dependencyProjectVersion(): BelongsTo
    {
        return $this->belongsTo(ProjectVersion::class, 'dependency_project_version_id');
    }

    /**
     * Get the project that is depended on (when no specific version is required)
     */
    public function dependencyProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'dependency_project_id');
    }

    /**
     * Get the background color class for this dependency type
     */
    public function bgColorClass(): Attribute
    {
        return Attribute::make(
            get: fn () => DependencyType::fromString($this->dependency_type)->bgColorClass()
        );
    }

    /**
     * Get the display name for this dependency type
     */
    public function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => DependencyType::fromString($this->dependency_type)->displayName()
        );
    }
}
