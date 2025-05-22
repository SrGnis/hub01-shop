<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class ProjectTagGroup extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_tag_group';

    protected $fillable = [
        'name',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saved(function ($tagGroup) {
            if ($tagGroup->projectTypes->isNotEmpty()) {
                foreach ($tagGroup->projectTypes as $projectType) {
                    Cache::forget('project_tag_groups_by_type_' . $projectType->value);
                }
            }
        });

        static::deleting(function ($tagGroup) {
            if ($tagGroup->projectTypes->isNotEmpty()) {
                foreach ($tagGroup->projectTypes as $projectType) {
                    Cache::forget('project_tag_groups_by_type_' . $projectType->value);
                }
            }
        });
    }

    /**
     * Get the tags for this tag group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags(): HasMany
    {
        return $this->hasMany(ProjectTag::class, 'project_tag_group_id');
    }

    /**
     * The project types that belong to the ProjectTagGroup
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
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
