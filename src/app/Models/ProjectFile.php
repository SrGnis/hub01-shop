<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Get the url to download the file
     */
    public function downloadUrl() : Attribute
    {
        return Attribute::make(
            get: function () {
                return route('file.download', [
                    'projectType' => $this->projectVersion->project->projectType,
                    'project' => $this->projectVersion->project,
                    'version' => $this->projectVersion,
                    'file' => $this
                ]);
            }
        );
    }

    /**
     * Get the SHA1 hash of the file
     */
    public function sha1(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!Storage::disk(static::getDisk())->exists($this->path)) {
                    return null;
                }

                return sha1_file(Storage::disk(static::getDisk())->path($this->path));
            }
        );
    }
}
