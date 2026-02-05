<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->display_name,
            'slug' => $this->value,
            'icon' => $this->icon,
        ];
    }
}
