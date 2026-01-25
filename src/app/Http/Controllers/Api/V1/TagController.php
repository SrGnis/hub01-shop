<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectTagResource;
use App\Http\Resources\ProjectVersionTagResource;
use App\Models\Project;
use App\Models\ProjectTag;
use App\Models\ProjectVersionTag;
use Illuminate\Http\Request;

class TagController extends Controller
{
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

    public function getProjectTagsBySlug(Request $request, string $slug)
    {
        $with = ['mainTag','projectTypes'];

        $tag = ProjectTag::where('slug', $slug)->with($with)->first();

        return ProjectTagResource::make($tag);
    }

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

    public function getProjectVersionTagBySlug(Request $request, string $slug){

        $with = ['mainTag','projectTypes'];

        $tag = ProjectVersionTag::where('slug', $slug)->with($with)->first();

        return ProjectVersionTagResource::make($tag);
    }
}
