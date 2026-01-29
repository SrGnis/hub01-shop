<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectVersionDependencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'project' => $this->dependency_name ? $this->dependency_name : ($this->dependencyProjectVersion ? $this->dependencyProjectVersion->project?->slug : $this->dependencyProject?->slug),
            'version' => $this->dependency_version ? $this->dependency_version : $this->dependencyProjectVersion?->version,
            'type' => $this->dependency_type,
            'external' => $this->dependency_name || $this->dependency_version
        ];
    }
}
