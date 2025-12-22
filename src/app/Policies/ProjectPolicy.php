<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Project $project): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only check if the user has verified their email
        // Quota validation is business logic and should be done in the service layer
        return $user->hasVerifiedEmail();
    }

    /**
     * Determine whether the user can edit the model.
     */
    public function edit(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // Admins can always update
        if ($user->isAdmin()) {
            return true;
        }

        // Users can only update their own projects if they are active members
        $isActiveMember = $project->users()->where('user_id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();

        if (!$isActiveMember) {
            return false;
        }

        // Users can edit rejected projects for resubmission
        if ($project->isRejected()) {
            return $project->owner->contains($user);
        }

        // Users can edit approved projects
        if ($project->isApproved()) {
            return true;
        }

        // Users cannot edit pending projects unless they are the owner (for fixes before approval)
        if ($project->isPending()) {
            return $project->owner->contains($user);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->users()->where('user_id', $user->id)
            ->wherePivot('primary', true)
            ->wherePivot('status', 'active')
            ->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine whether the user can add members to the project.
     */
    public function addMember(User $user, Project $project): bool
    {
        return $project->users()->where('user_id', $user->id)
            ->wherePivot('status', 'active')
            ->wherePivot('primary', true)
            ->exists();
    }

    /**
     * Determine whether the user can upload versions to the project.
     */
    public function uploadVersion(User $user, Project $project): bool
    {
        return $project->users()->where('user_id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();
    }

    /**
     * Determine whether the user can edit versions of the project.
     */
    public function editVersion(User $user, Project $project): bool
    {
        return $this->uploadVersion($user, $project);
    }

    /**
     * Determine whether the user (admin) can approve a project.
     */
    public function approve(User $user, Project $project): bool
    {
        return $user->isAdmin() && $project->isPending();
    }

    /**
     * Determine whether the user (admin) can reject a project.
     */
    public function reject(User $user, Project $project): bool
    {
        return $user->isAdmin() && $project->isPending();
    }

    /**
     * Determine whether the user can resubmit a rejected project.
     */
    public function resubmit(User $user, Project $project): bool
    {
        return $project->isRejected() && $project->owner->contains($user);
    }
}
