<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingPasswordChange extends Model
{
    protected $fillable = [
        'user_id',
        'hashed_password',
        'verification_token',
        'status',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user that owns this pending password change
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the verification token is still valid
     */
    public function isVerificationTokenValid(): bool
    {
        return $this->status === 'pending_verification' && $this->expires_at->isFuture();
    }

    /**
     * Mark the password change as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'status' => 'completed',
            'verified_at' => now(),
        ]);
    }
}

