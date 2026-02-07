<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectTagResource;
use App\Http\Resources\ProjectVersionTagResource;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectVersionTag;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * List project tags
     *
     * Returns a list of project tags
     */
    #[QueryParameter(name: 'plain', description: 'Whether to return the tags in a hierarchical structure or a flat list', default: false)]
    #[QueryParameter(name: 'project_type', description: 'The project type to filter by (subtags of main tags with this project type will be included)', default: null)]
    public function getProjectTags(Request $request){

        $validated = $request->validate([
            'project_type' => 'string|nullable|exists:project_type,value'
        ]);

        $projectType = $request->query('project_type');

        $with = ['mainTag','projectTypes'];

        $query = ProjectTag::query();

        if($request->has('plain')){
            $query = ProjectTag::with($with);
        }else{
            $with[] = 'subTags';
            $query = ProjectTag::onlyMain()->with($with);
        }

        // Where has the project type or the parent has the project type
        if($projectType){
            $query->where(function($query) use ($projectType){
                // Tag has the project type directly
                $query->whereHas('projectTypes', function($query) use ($projectType){
                    $query->where('value', $projectType);
                })
                // OR the parent tag has the project type
                ->orWhereHas('parent.projectTypes', function($query) use ($projectType){
                    $query->where('value', $projectType);
                });
            });
        }

        $tags = $query->get();

        return ProjectTagResource::collection($tags);
    }

    /**
     * Get a project tag
     *
     * Returns the project tag with the given slug
     */
    public function getProjectTagsBySlug(Request $request, string $slug)
    {
        $with = ['mainTag','projectTypes'];

        $tag = ProjectTag::where('slug', $slug)->with($with)->first();

        abort_if(!$tag, 404, 'Project tag not found');

        return ProjectTagResource::make($tag);
    }

    /**
     * List project version tags
     *
     * Returns a list of project version tags
     */
    #[QueryParameter(name: 'plain', description: 'Whether to return the tags in a hierarchical structure or a flat list', default: false)]
    #[QueryParameter(name: 'project_type', description: 'The project type to filter by (subtags of main tags with this project type will be included)', default: null)]
    public function getProjectVersionTags(Request $request){

        $validated = $request->validate([
            'project_type' => 'string|nullable|exists:project_type,value'
        ]);

        $with = ['mainTag','projectTypes'];

        $projectType = $request->input('project_type');

        $query = ProjectVersionTag::query();

        if($request->has('plain')){
            $query = ProjectVersionTag::with($with);
        }else{
            $with[] = 'subTags';
            $query = ProjectVersionTag::onlyMain()->with($with);
        }

        if($projectType){
            $query->where(function($query) use ($projectType){
                // Tag has the project type directly
                $query->whereHas('projectTypes', function($query) use ($projectType){
                    $query->where('value', $projectType);
                })
                // OR the parent tag has the project type
                ->orWhereHas('parent.projectTypes', function($query) use ($projectType){
                    $query->where('value', $projectType);
                });
            });
        }

        $tags = $query->get();

        return ProjectVersionTagResource::collection($tags);
    }

    /**
     * Get a project version tag
     *
     * Returns the project version tag with the given slug
     */
    public function getProjectVersionTagBySlug(Request $request, string $slug){

        $with = ['mainTag','projectTypes'];

        $tag = ProjectVersionTag::where('slug', $slug)->with($with)->first();

        abort_if(!$tag, 404, 'Project version tag not found');

        return ProjectVersionTagResource::make($tag);
    }
}
