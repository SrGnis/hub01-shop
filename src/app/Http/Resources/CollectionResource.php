<?php

namespace App\Http\Resources;

use App\Enums\CollectionSystemType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isOwner = $request->user() && $request->user()->id === $this->user_id;

        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'description' => $this->description,
            'visibility' => $this->visibility?->value,
            'system_type' => $this->system_type?->value,
            'is_system' => $this->system_type !== null,
            'is_favorites' => $this->system_type === CollectionSystemType::FAVORITES,
            'owner' => $this->when($this->relationLoaded('user'), fn () => [
                'username' => $this->user?->name,
            ]),
            'hidden_share_token' => $this->when($isOwner, fn () => $this->hidden_share_token),
            'entries' => CollectionEntryResource::collection($this->whenLoaded('entries')),
            /**
             * @var string
             * @format datetime
             */
            'created_at' => $this->created_at?->toDateTimeString(),
            /**
             * @var string
             * @format datetime
             */
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

