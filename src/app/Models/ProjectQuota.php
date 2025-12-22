<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectQuota extends Model
{
    use HasFactory;

    protected $table = 'project_quota';

    protected $fillable = [
        'project_id',
        'pending_projects_max',
        'total_storage_max',
        'project_storage_max',
        'versions_per_day_max',
        'version_size_max',
        'files_per_version_max',
        'file_size_max',
    ];

    protected $casts = [
        'pending_projects_max' => 'integer',
        'total_storage_max' => 'integer',
        'project_storage_max' => 'integer',
        'versions_per_day_max' => 'integer',
        'version_size_max' => 'integer',
        'files_per_version_max' => 'integer',
        'file_size_max' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
