<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user is an admin and should be granted all abilities.
     *
     * @return bool|void
     */
    public function before(?User $user)
    {
        if ($user && $user->isAdmin()) {
            return true;
        }
    }
}
