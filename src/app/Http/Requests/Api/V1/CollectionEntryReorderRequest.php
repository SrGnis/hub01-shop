<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CollectionEntryReorderRequest extends FormRequest
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
            'entry_uids' => 'required|array|min:1',
            'entry_uids.*' => 'required|string|distinct|exists:collection_entry,uid',
        ];
    }
}

