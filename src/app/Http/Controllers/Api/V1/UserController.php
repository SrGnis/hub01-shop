<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectCollection;
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
        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }
        return UserResource::make($user);
    }

    /**
     * Get a user's projects.
     */
    public function getUserProjects(Request $request, string $name){
        $user = User::where('name', $name)->first();
        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }

        $projects = Project::exclude(['description'])
            ->whereHas('memberships', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'active');
            })->paginate();

        return ProjectCollection::make($projects);
    }
}
