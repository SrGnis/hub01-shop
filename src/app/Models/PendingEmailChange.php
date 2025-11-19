<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingEmailChange extends Model
{
    /** @use HasFactory<\Database\Factories\PendingEmailChangeFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'old_email',
        'new_email',
        'authorization_token',
        'verification_token',
        'status',
        'authorization_expires_at',
        'verification_expires_at',
        'authorized_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'authorization_expires_at' => 'datetime',
            'verification_expires_at' => 'datetime',
            'authorized_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Get the user associated with this pending email change
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the authorization token is still valid
     */
    public function isAuthorizationTokenValid(): bool
    {
        return $this->status === 'pending_authorization' && 
               $this->authorization_expires_at->isFuture();
    }

    /**
     * Check if the verification token is still valid
     */
    public function isVerificationTokenValid(): bool
    {
        return $this->status === 'pending_verification' && 
               $this->verification_expires_at->isFuture();
    }

    /**
     * Mark as authorized
     */
    public function markAsAuthorized(string $verificationToken): void
    {
        $this->update([
            'status' => 'pending_verification',
            'verification_token' => $verificationToken,
            'authorized_at' => now(),
            'verification_expires_at' => now()->addDay(),
        ]);
    }

    /**
     * Mark as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'status' => 'completed',
            'verified_at' => now(),
        ]);
    }
}

