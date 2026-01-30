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
    public function getProjectTags(Request $request){

        $with = ['mainTag','projectTypes'];

        if($request->has('plain')){
            $tags = ProjectTag::with($with)->get();
        }else{
            $with[] = 'subTags';
            $tags = ProjectTag::onlyMain()->with($with)->get();
        }

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
    public function getProjectVersionTags(Request $request){

        $with = ['mainTag','projectTypes'];

        if($request->has('plain')){
            $tags = ProjectVersionTag::with($with)->get();
        }else{
            $with[] = 'subTags';
            $tags = ProjectVersionTag::onlyMain()->with($with)->get();
        }

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
