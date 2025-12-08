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
        return $project->users()->where('user_id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();
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
}
