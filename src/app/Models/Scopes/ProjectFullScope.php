<?php

namespace App\Models\Scopes;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class ProjectFullScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * 
     * Pending projects are excluded from public queries unless:
     * - The user is an admin
     * - The user is the project owner
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->withStats();

        // Exclude pending projects from public searches
        $builder->approved();

        // Exclude deactivated projects from public searches
        $builder->whereNull('deactivated_at');
    }
}
