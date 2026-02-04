<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectCollection;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get a user by name.
     */
    public function getUserByName(Request $request, string $name){
        $user = User::where('name', $name)->first();

        abort_if(!$user, 404, 'User not found');

        return UserResource::make($user);
    }

    /**
     * Get a user's projects.
     */
    public function getUserProjects(Request $request, string $name){
        $user = User::where('name', $name)->first();

        abort_if(!$user, 404, 'User not found');

        $projects = Project::exclude(['description'])
            ->globalSearchScope()
            ->whereHas('memberships', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'active');
            })->paginate(10);

        return ProjectResource::collection($projects);
    }
}
