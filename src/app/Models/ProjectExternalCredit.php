<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperProjectExternalCredit
 */
class ProjectExternalCredit extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectExternalCreditFactory> */
    use HasFactory;

    protected $table = 'project_external_credit';

    protected $fillable = [
        'name',
        'role',
        'url',
    ];

    /**
     * Get the project associated with this external credit.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

