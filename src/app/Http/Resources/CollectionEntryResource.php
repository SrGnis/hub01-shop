<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'sort_order' => (int) $this->sort_order,
            'note' => $this->note,
            'project' => $this->when(
                $this->relationLoaded('project'),
                fn () => $this->project ? ProjectResource::make($this->project) : null
            ),
        ];
    }
}

