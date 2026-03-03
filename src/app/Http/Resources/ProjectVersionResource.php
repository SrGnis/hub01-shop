<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectVersionResource extends JsonResource
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
            'version' => $this->version,
            'release_type' => $this->release_type,
            /**
             * @format date
             */
            'release_date' => $this->release_date->toDateString(),
            'changelog' => $this->changelog,
            /**
             * @var int
             */
            'downloads' => $this->downloads ? $this->downloads : 0,
            /**
             * List of version tag slugs
             * @var string[]
             */
            'tags' => $this->tags->pluck('slug'),
            'files' => ProjectFileResource::collection($this->whenLoaded('files')),
            'dependencies' => ProjectVersionDependencyResource::collection($this->whenLoaded('dependencies')),
        ];
    }
}
