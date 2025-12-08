<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperMembership
 */
class Membership extends Pivot
{
    protected $table = 'membership';

    public $incrementing = true;

    protected $fillable = [
        'role',
        'primary',
        'status',
    ];

    protected $casts = [
        'primary' => 'boolean',
        'status' => 'string',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted() {}

    /**
     * Get the User associated with the Membership
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the Project associated with the Membership
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the Mod associated with the Membership (legacy method for backward compatibility)
     *
     * @deprecated Use project() instead
     */
    public function mod(): BelongsTo
    {
        return $this->project();
    }

    /**
     * Get the user who sent the invitation
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scope a query to only include pending invitations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include active memberships.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
