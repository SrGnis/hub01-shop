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
            // Core project information
            'name' => $this->name,
            'slug' => $this->slug,

            // Content
            'summary' => $this->summary,
            'description' => $this->when($this->description, $this->description),
            'logo_url' => $this->getLogoUrl(),

            // Links
            'website' => $this->website,
            'issues' => $this->issues,
            'source' => $this->source,

            // Type and tags
            'type' => $this->when($this->whenLoaded('projectType'), fn () => $this->projectType?->value),
            'tags' => $this->when($this->whenLoaded('tags'), fn () => $this->tags->pluck('slug')),

            // Status
            'status' => $this->status,

            // Active members
            'members' => $this->when($this->whenLoaded('active_users'), fn () => $this->active_users->map(function ($user) {
                return [
                    'username' => $user->name,
                    'role' => $user->pivot->role,
                ];
            })),

            // Statistics
            'downloads' => $this->downloads ?? 0,
            'last_release_date' => $this->recentReleaseDate?->toDateString(),
            'version_count' => $this->when($this->whenLoaded('versions'), fn () => $this->versions->count()),

            // Timestamp
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
