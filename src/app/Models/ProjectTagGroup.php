<?php

namespace App\Models;

use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Str;

/**
 * @mixin IdeHelperProjectTagGroup
 */
class ProjectTagGroup extends Model
{
    use HasFactory, HasUniqueSlug;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_tag_group';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'display_priority',
    ];

    protected $casts = [
        'display_priority' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('display_priority_order', function (Builder $builder) {
            $builder
                ->orderByDesc('display_priority')
                ->orderBy('slug');
        });

        static::saved(function ($tagGroup) {
            if ($tagGroup->projectTypes->isNotEmpty()) {
                foreach ($tagGroup->projectTypes as $projectType) {
                    Cache::forget('project_tag_groups_by_type_'.$projectType->value);
                }
            }
        });

        static::deleting(function ($tagGroup) {
            if ($tagGroup->projectTypes->isNotEmpty()) {
                foreach ($tagGroup->projectTypes as $projectType) {
                    Cache::forget('project_tag_groups_by_type_'.$projectType->value);
                }
            }
        });
    }

    /**
     * Get the tags for this tag group
     */
    public function tags(): HasMany
    {
        return $this->hasMany(ProjectTag::class, 'project_tag_group_id');
    }

    /**
     * The project types that belong to the ProjectTagGroup
     */
    public function projectTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectType::class,
            'project_tag_group_project_type',
            'tag_group_id',
            'project_type_id'
        );
    }

}
