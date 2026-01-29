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
            'version' => $this->version,
            'release_type' => $this->release_type?->value ?? $this->release_type,
            'release_date' => $this->release_date->toDateString(),
            'changelog' => $this->changelog,
            'downloads' => $this->downloads,
            'tags' => $this->tags->pluck('slug'),
            'files' => ProjectFileResource::collection($this->whenLoaded('files')),
            'dependencies' => ProjectVersionDependencyResource::collection($this->whenLoaded('dependencies')),
        ];
    }
}
