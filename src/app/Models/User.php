<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory,
        Notifiable,
        TwoFactorAuthenticatable,
        SoftDeletes,
        HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'role',
        'avatar',
        'unverified_deletion_warning_sent_at',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'email',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * Check if the user is deactivated
     */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'name';
    }

    /**
     * Get the memberships associated with the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get the projects associated with the user through memberships
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'membership')
            ->withPivot(['role', 'primary', 'status'])
            ->wherePivot('status', 'active')
            ->using(Membership::class);
    }

    /**
     * Get the projects where the user is the primary owner
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ownedProjects()
    {
        return $this->belongsToMany(Project::class, 'membership')
            ->withPivot(['role', 'primary', 'status'])
            ->wherePivot('status', 'active')
            ->wherePivot('primary', true)
            ->using(Membership::class);
    }

    /**
     * Get the pending project invitations for the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pendingInvitations()
    {
        return $this->memberships()->pending();
    }

    /**
     * Get the active memberships for the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeMemberships()
    {
        return $this->memberships()->active();
    }

    /**
     * Get the projects where the user has sent invitations
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function sentInvitations()
    {
        return Membership::where('invited_by', $this->id)->pending()->get();
    }

    public function quota()
    {
        return $this->hasOne(UserQuota::class);
    }

    /**
     * Check if the user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get the avatar URL for the user
     */
    public function getAvatarUrl(): ?string
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/'.$this->avatar);
        }

        return null;
    }

    /**
     * Get the pending email changes for the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pendingEmailChanges()
    {
        return $this->hasMany(PendingEmailChange::class);
    }

    /**
     * Get the pending password changes for the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pendingPasswordChanges()
    {
        return $this->hasMany(PendingPasswordChange::class);
    }

    /**
     * Get the projects reviewed by this user (admin)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviewedProjects()
    {
        return $this->hasMany(Project::class, 'reviewed_by');
    }

    /**
     * Scope a query to only include users matching the search term.
     */
    #[Scope]
    protected function searchScope($query, $term): void
    {
        $query->where(function ($query) use ($term) {
            $query->where('name', 'like', '%' . $term . '%')
                ->orWhere('email', 'like', '%' . $term . '%');
        });
    }
}
