<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;

class MembershipPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can accept the membership.
     *
     * Only the user who was invited can accept the membership.
     * Only if the membership is still pending.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Membership  $membership
     */
    public function accept(User $user, Membership $membership)
    {
        return $user->id === $membership->user_id && $membership->status === 'pending';
    }

    /**
     * Determine whether the user can reject the membership.
     *
     * Only the user who was invited can reject the membership.
     * Only if the membership is still pending.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Membership  $membership
     */
    public function reject(User $user, Membership $membership)
    {
        return $user->id === $membership->user_id && $membership->status === 'pending';
    }

    /**
     * Determine whether the user can delete the membership.
     *
     * Only primary users can delete the memberships.
     * Or the user who is the owner of the membership.
     * There always has to be at least one primary user.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Membership  $membership
     */
    public function delete(User $user, Membership $membership)
    {
        $project = $membership->project;

        // Check if user is the owner of the membership being deleted
        $isOwner = $user->id === $membership->user_id;

        // Check if user is a primary member of the project
        $isPrimary = $project->memberships()
            ->where('user_id', $user->id)
            ->where('primary', true)
            ->where('status', 'active')
            ->exists();

        // User must be either a primary member or the owner of the membership
        if (! $isPrimary && ! $isOwner) {
            return false;
        }

        // If deleting a primary membership, ensure at least one primary user remains
        if ($membership->primary) {
            $primaryCount = $project->memberships()
                ->where('primary', true)
                ->where('status', 'active')
                ->count();

            // Cannot delete if this is the last primary user
            if ($primaryCount <= 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can set a membership as primary.
     *
     * Only primary users can set a membership as primary.
     * Only memberships with status 'active' can be set as primary.
     * Only if the user is not already a primary member.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Membership  $membership
     */
    public function setPrimary(User $user, Membership $membership)
    {
        // Only active memberships can be set as primary
        if ($membership->status !== 'active') {
            return false;
        }

        // Cannot set as primary if already primary
        if ($membership->primary) {
            return false;
        }

        $project = $membership->project;

        // Only primary users can set a membership as primary
        return $project->memberships()
            ->where('user_id', $user->id)
            ->where('primary', true)
            ->where('status', 'active')
            ->exists();
    }

}
