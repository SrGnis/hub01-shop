<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProjectIndexRequest extends FormRequest
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
            'project_type' => 'nullable|string|exists:project_type,value',
            'search' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|exists:project_tag,slug',
            'version_tags' => 'nullable|array',
            'version_tags.*' => 'string|exists:project_version_tag,slug',
            'order_by' => 'nullable|string|in:name,created_at,updated_at,downloads,favorites',
            'order_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'release_date_period' => 'nullable|string|in:all,last_30_days,last_90_days,last_year,custom',
            'release_date_start' => 'nullable|date',
            'release_date_end' => 'nullable|date|after_or_equal:release_date_start',
        ];
    }
}
