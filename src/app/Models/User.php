<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
        ];
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
     * This relationship includes soft-deleted projects
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'membership')
            ->withPivot(['role', 'primary', 'status'])
            ->using(Membership::class);
    }

    /**
     * Get the projects where the user is the primary owner
     * This relationship includes soft-deleted projects
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ownedProjects()
    {
        return $this->belongsToMany(Project::class, 'membership')
            ->withPivot(['role', 'primary', 'status'])
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

    /**
     * Check if the user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
