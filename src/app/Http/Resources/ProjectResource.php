<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'summary' => $this->summary,
            /** Null in collection responses
             * @var string | null
            */
            'description' => $this->description,
            'logo_url' => $this->getLogoUrl(),
            'website' => $this->website,
            'issues' => $this->issues,
            'source' => $this->source,
            'type' => $this->when($this->whenLoaded('projectType'), fn () => $this->projectType?->value),
            /**
             * List of the slugs of the associated tags
             * @var array<string> | null
             */
            'tags' => $this->when($this->whenLoaded('tags'), fn () => $this->tags->pluck('slug')),
            /**
             * The status of the project, can be either 'active' or 'inactive'
             */
            'status' => $this->status,
            'members' => $this->when($this->whenLoaded('active_users'), fn () => $this->active_users->map(function ($user) {
                return [
                    'username' => $user->name,
                    'role' => $user->pivot->role,
                ];
            })),
            /**
             * @var int
             */
            'downloads' => $this->downloads ?? 0,
            /**
             * @var string | null
             * @format date
             */
            'last_release_date' => $this->recentReleaseDate?->toDateString(),
            'version_count' => $this->when($this->whenLoaded('versions'), fn () => $this->versions->count()),
            /**
             * @var string | null
             * @format date
             */
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
