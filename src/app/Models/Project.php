<?php

namespace App\Models;

use App\Models\Scopes\ProjectFullScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ScopedBy(ProjectFullScope::class)]
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project';

    protected $fillable = [
        'name',
        'slug',
        'summary',
        'description',
        'logo_path',
        'website',
        'issues',
        'source',
        'status',
        'project_type_id',
    ];

    protected $with = [
        'projectType',
        'tags.tagGroup',
        'owner',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the project type for this project
     */
    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
    }

    /**
     * Get the versions for the project
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ProjectVersion::class);
    }

    /**
     * Get all project versions that depend on this project (not a specific version)
     */
    public function dependedOnBy(): HasMany
    {
        return $this->hasMany(ProjectVersionDependency::class, 'dependency_project_id');
    }

    /**
     * The tags that belong to the Project
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectTag::class,
            'project_project_tag',
            'project_id',
            'tag_id'
        );
    }

    /**
     * Get the memberships associated with the project
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get the users associated with the project through memberships
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'membership')
            ->withPivot(['role', 'primary', 'status'])
            ->using(Membership::class);
    }

    /**
     * Get the users associated with the project through memberships
     */
    public function active_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'membership')
            ->withPivot(['role', 'primary', 'status'])
            ->wherePivot('status', 'active')
            ->using(Membership::class);
    }

    /**
     * Get the owner of the project (user with primary membership)
     */
    public function owner(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'membership')
            ->withPivot(['role', 'primary', 'status'])
            ->wherePivot('primary', true)
            ->using(Membership::class);
    }

    /**
     * Generate a unique slug based on the project name.
     *
     * @param  string|null  $customSlug  Optional custom slug to use instead of generating from name
     * @return string The generated unique slug
     */
    public function generateSlug(?string $customSlug = null): string
    {
        $slug = $customSlug ? Str::slug($customSlug) : Str::slug($this->name);
        $originalSlug = $slug;
        $counter = 1;

        // Check if the slug already exists
        while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        return $slug;
    }

    /**
     * Get the pretty name attribute
     */
    public function prettyName(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::title($this->name)
        );
    }

    /**
     * Get the recent versions attribute
     */
    public function recentVersions(int $limit = 3): Attribute
    {
        return Attribute::make(
            get: function () use ($limit) {
                $cacheKey = 'project_recent_versions_'.$this->id.'_'.$limit;

                return Cache::remember($cacheKey, now()->addHours(24), function () use ($limit) {
                    $latestRelease = $this->versions->where('release_type', 'release')->sortByDesc('release_date')->first();
                    $otherVersions = $this->versions->sortByDesc('release_date')->take($limit);
                    $allVersions = $latestRelease ? collect([$latestRelease])->merge($otherVersions) : $otherVersions;

                    return $allVersions->unique('id')->take($limit);
                });
            }
        );
    }

    /**
     * Clear the recent versions cache for this project
     */
    public function clearRecentVersionsCache(): void
    {
        Cache::forget('project_recent_versions_'.$this->id.'_3');
        Cache::forget('project_recent_versions_'.$this->id.'_5');
        Cache::forget('project_recent_versions_'.$this->id.'_10');
    }

    /**
     * Get the total size of all files in the project
     */
    public function size(): Attribute
    {
        return Attribute::make(
            get: function () {
                $cacheKey = 'project_size_'.$this->id;

                return Cache::remember($cacheKey, now()->addHours(24), function () {
                    return $this->versions()
                        ->with('files')
                        ->get()
                        ->flatMap(function ($version) {
                            return $version->files;
                        })
                        ->sum('size');
                });
            }
        );
    }

    /**
     * Clear the size cache for this project
     */
    public function clearSizeCache(): void
    {
        Cache::forget('project_size_'.$this->id);
    }

    /**
     * Format the size in a human-readable format
     */
    public function getFormattedSizeAttribute(): string
    {
        $size = $this->size;

        if ($size < 1024) {
            return $size.' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2).' KB';
        } elseif ($size < 1073741824) {
            return round($size / 1048576, 2).' MB';
        } else {
            return round($size / 1073741824, 2).' GB';
        }
    }

    /**
     * Get the logo URL for the project
     */
    public function getLogoUrl(): string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return asset('storage/'.$this->logo_path);
        }

        return asset('images/placeholder.png');
    }
}
