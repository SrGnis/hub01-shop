<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CollectionPublicIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'order_by' => 'nullable|string|in:name,created_at,updated_at',
            'order_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|in:10,25,50,100',
        ];
    }
}

