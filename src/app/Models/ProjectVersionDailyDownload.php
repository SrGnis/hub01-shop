<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperProjectVersionDailyDownload
 */
class ProjectVersionDailyDownload extends Model
{
    use HasFactory;

    protected $table = 'project_version_daily_download';

    protected $fillable = [
        'project_version_id',
        'date',
        'downloads',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function projectVersion(): BelongsTo
    {
        return $this->belongsTo(ProjectVersion::class);
    }
}
