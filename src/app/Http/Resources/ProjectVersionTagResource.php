<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectVersionTagResource extends JsonResource
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
            'tag_group' => $this->tagGroup?->slug,
            'project_types' => $this->projectTypes()->pluck('value'),
            'sub_tags' => $this->when($this->whenLoaded('subTags', default: null), fn () => ProjectVersionTagResource::collection($this->subTags)),
            'main_tag' => $this->mainTag?->slug,
        ];
    }
}
