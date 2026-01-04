<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperProjectFile
 */
class ProjectFile extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFileFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_file';

    protected $fillable = [
        'name',
        'path',
        'size',
    ];

    public static function getDisk(): string
    {
        return config('projects.files-disk');
    }

    public static function getDirectory(): string
    {
        return config('projects.files-directory');
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'name';
    }

    /**
     * Get the project version that owns the project file
     */
    public function projectVersion(): BelongsTo
    {
        return $this->belongsTo(ProjectVersion::class);
    }
}
