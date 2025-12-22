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
        $builder->withSum('versions as downloads', 'downloads');
        $builder->withMax('versions as recent_release_date', 'release_date');

        // Exclude pending projects from public searches
        // Only show approved projects, or pending/rejected projects if user is owner/admin
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            $builder->where(function ($query) use ($user) {
                // Always show approved projects
                $query->where('approval_status', ApprovalStatus::APPROVED);
                
                // If user is logged in, also show their own pending/rejected projects
                if ($user) {
                    $query->orWhere(function ($subQuery) use ($user) {
                        $subQuery->whereIn('approval_status', [ApprovalStatus::PENDING, ApprovalStatus::REJECTED])
                            ->whereHas('owner', function ($ownerQuery) use ($user) {
                                $ownerQuery->where('user_id', $user->id);
                            });
                    });
                }
            });
        }
        // Admins can see all projects regardless of approval status
    }
}
