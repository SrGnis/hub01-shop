<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function getProjectBySlug(Request $request, string $slug){
        $project = Project::where('slug', $slug)->first();

        if(!$project){
            return response()->json(['message' => 'Project not found'], 404);
        }

        return ProjectResource::make($project);
    }
}
