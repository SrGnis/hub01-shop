<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CollectionEntryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project' => 'required|string|exists:project,slug',
            'note' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}

