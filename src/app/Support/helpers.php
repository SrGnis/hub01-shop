<?php

declare(strict_types=1);

use App\Models\ProjectType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

if (! function_exists('project_logo_url')) {
    function project_logo_url(?string $logoPath): string
    {
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            return asset('storage/'.$logoPath);
        }

        return asset('images/placeholder.png');
    }
}

if (! function_exists('all_project_types')) {
    function all_project_types(): Collection
    {
        return ProjectType::query()->get();
    }
}
