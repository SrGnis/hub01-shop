<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Models\Scopes\ProjectFullScope;
use App\Traits\ExcludeScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperProject
 */
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    use SoftDeletes;

    use ExcludeScope;

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
        'deactivated_at',
        'approval_status',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'deactivated_at' => 'datetime',
            'approval_status' => ApprovalStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Check if the project is deactivated
     */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

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
     * The main tags that belong to the Project (tags without a parent)
     */
    public function mainTags(): BelongsToMany
    {
        return $this->tags()->whereNull('parent_id');
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
     * Get the admin who reviewed this project
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the quota overrides for this project
     */
    public function quota(): HasOne
    {
        return $this->hasOne(ProjectQuota::class);
    }

    /**
     * Scope query to global search restrictions
     */
    #[Scope]
    protected function globalSearchScope(Builder $query): void
    {
        $query->withStats();
        $query->withRelations();

        // Exclude draft and pending projects from public searches
        $query->approved();

        // Exclude deactivated projects from public searches
        $query->whereNull('deactivated_at');
    }

    /**
     * Scope query to user based access restrictions
     */
    #[Scope]
    protected function accessScope(Builder $query): void
    {
        $query->withStats();
        $query->withRelations();

        $user = Auth::user();

        // Return only approved projects and non-deactivated projects by default
        // If the user is a member of the project, no restrictions apply
        if ($user) {
            $query->where(function (Builder $query) use ($user) {
                // Show approved and non-deactivated projects to everyone
                $query->where(function (Builder $query) {
                    $query->approved()
                        ->whereNull('deactivated_at');
                })
                // OR show projects where the user is a member (regardless of status)
                ->orWhereHas('memberships', function (Builder $query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            });
        } else {
            // Guest users only see approved and non-deactivated projects
            $query->approved()
                ->whereNull('deactivated_at');
        }
    }
    /**
     * Scope query with stats
     */
    #[Scope]
    protected function withStats(Builder $query): void
    {
        $query->withSum('versions as downloads', 'downloads');
        $query->withMax('versions as recent_release_date', 'release_date');
        // Add last update time as the greatest of the project updated_at and the maximum of all version updated_at
        $query->addSelect([
            'last_update_time' => ProjectVersion::selectRaw(
                'GREATEST(project.updated_at, COALESCE(MAX(project_version.updated_at), project.updated_at))'
            )->whereColumn('project_version.project_id', 'project.id'),
        ]);
    }

    /**
     * Scope query to include relations
     */
    #[Scope]
    protected function withRelations(Builder $query): void
    {
        $query->with([
            'projectType',
            'tags.tagGroup',
            'owner',
        ]);
    }

    /**
     * Scope query to deactivated projects
     */
    #[Scope]
    protected function deactivated(Builder $query): void
    {
        $query->whereNotNull('deactivated_at');
    }

    /**
     * Scope query to draft projects
     */
    #[Scope]
    protected function draft(Builder $query): void
    {
        $query->where('approval_status', ApprovalStatus::DRAFT);
    }

    /**
     * Scope query to pending projects
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('approval_status', ApprovalStatus::PENDING);
    }

    /**
     * Scope query to approved projects
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('approval_status', ApprovalStatus::APPROVED);
    }

    /**
     * Scope query to rejected projects
     */
    #[Scope]
    protected function rejected(Builder $query): void
    {
        $query->where('approval_status', ApprovalStatus::REJECTED);
    }

    /**
     * Check if the project is in draft status
     */
    public function isDraft(): bool
    {
        return $this->approval_status === ApprovalStatus::DRAFT;
    }

    /**
     * Check if the project is pending approval
     */
    public function isPending(): bool
    {
        return $this->approval_status === ApprovalStatus::PENDING;
    }

    /**
     * Check if the project is approved
     */
    public function isApproved(): bool
    {
        return $this->approval_status === ApprovalStatus::APPROVED;
    }

    /**
     * Check if the project is rejected
     */
    public function isRejected(): bool
    {
        return $this->approval_status === ApprovalStatus::REJECTED;
    }

    /**
     * Submit the project for review
     */
    public function submit(): void
    {
        $this->submitted_at = now();
        $this->approval_status = ApprovalStatus::PENDING;
        $this->save();
    }

    /**
     * Approve the project
     */
    public function approve(User $admin): void
    {
        $this->approval_status = ApprovalStatus::APPROVED;
        $this->reviewed_at = now();
        $this->reviewed_by = $admin->id;
        $this->rejection_reason = null;
        $this->save();
    }

    /**
     * Reject the project with a reason
     */
    public function reject(User $admin, string $reason): void
    {
        $this->approval_status = ApprovalStatus::REJECTED;
        $this->reviewed_at = now();
        $this->reviewed_by = $admin->id;
        $this->rejection_reason = $reason;
        $this->save();
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
     * Get number of downloads
     */
    public function downloads(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->versions->sum('downloads')
        );
    }

    /**
     * Get the recent release date attribute
     */
    public function recentReleaseDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->versions->sortByDesc('release_date')->first()?->release_date
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
