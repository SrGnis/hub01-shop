<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\DependencyType;
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
            /**
             * The project slug of the dependency
             * @var string
             */
            'project' => $this->dependency_name ? $this->dependency_name : ($this->dependencyProjectVersion ? $this->dependencyProjectVersion->project?->slug : $this->dependencyProject?->slug),
            /**
             * The version of the dependency
             * @var string | null
             */
            'version' => $this->dependency_version ? $this->dependency_version : $this->dependencyProjectVersion?->version,
            /**
             * @var DependencyType
             */
            'type' => $this->dependency_type,
            /**
             * Whether the dependency is external (not a project on this platform)
             * @var boolean
             */
            'external' => $this->dependency_name || $this->dependency_version
        ];
    }
}
