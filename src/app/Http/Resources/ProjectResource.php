<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

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
             * External contributors declared by the project team.
             * @var array<int, array{name:string,role:string,url:string|null}> | null
             */
            'external_credits' => $this->when($this->whenLoaded('externalCredits'), fn () => $this->externalCredits->map(function ($credit) {
                return [
                    'name' => $credit->name,
                    'role' => $credit->role,
                    'url' => $credit->url,
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
             * Last update time of the project or one of its versions
             * @var string | null
             * @format datetime
             */
            'updated_at' => Carbon::parse($this->last_update_time)->toDateTimeString(),
            /**
             * @var string | null
             * @format datetime
             */
            'created_at' => Carbon::parse($this->created_at)->toDateTimeString(),
        ];
    }
}
