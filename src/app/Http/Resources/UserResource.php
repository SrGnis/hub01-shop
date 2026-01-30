<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'username' => $this->name,
            'bio' => $this->bio,
            /**
             * @var string | null
             * @format url
             */
            'avatar' => $this->getAvatarUrl(),
            /**
             * @var string
             * @format date
             */
            'created_at' => $this->created_at->toDateString(),
        ];
    }
}
