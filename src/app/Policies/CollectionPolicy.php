<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;

class CollectionPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any collections.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a collection.
     *
     * Token is used only for hidden collections and owner bypass is always allowed.
     */
    public function view(?User $user, Collection $collection, ?string $token = null): bool
    {
        if ($user && $user->id === $collection->user_id) {
            return true;
        }

        if ($collection->isPublic()) {
            return true;
        }

        if ($collection->isHidden()) {
            return $token !== null
                && $collection->hidden_share_token !== null
                && hash_equals($collection->hidden_share_token, $token);
        }

        return false;
    }

    /**
     * Determine whether the user can create collections.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update a collection.
     */
    public function update(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id;
    }

    /**
     * Determine whether the user can delete a collection.
     */
    public function delete(User $user, Collection $collection): bool
    {
        if ($user->id !== $collection->user_id) {
            return false;
        }

        return !$collection->isSystem();
    }

    /**
     * Determine whether the user can manage entries in a collection.
     */
    public function manageEntries(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id;
    }

    /**
     * Determine whether a hidden collection can be viewed with token flow.
     */
    public function viewHiddenByToken(?User $user, Collection $collection, string $token): bool
    {
        return $this->view($user, $collection, $token);
    }
}

