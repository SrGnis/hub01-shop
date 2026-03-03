<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectTagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            /**
             * @var string | null
             */
            'tag_group' => $this->tagGroup?->slug,
            /**
             * @var array<string>
             * Array of project type slugs
             */
            'project_types' => $this->projectTypes->pluck('value'),
            /**
             * Unset when the request does not have the `plain` query parameter or is a single query
             */
            'sub_tags' => $this->when($this->whenLoaded('subTags', default: null), fn () => ProjectTagResource::collection($this->subTags)),
            /**
             * @var string | null
             */
            'main_tag' => $this->mainTag?->slug,
        ];
    }
}
