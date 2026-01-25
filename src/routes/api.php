<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TagController as TagControllerV1;
use App\Http\Controllers\Api\V1\ProjectController as ProjectControllerV1;

Route::prefix('v1')->group(function () {
    // Tags
    Route::get('/project_tags', [TagControllerV1::class, 'getProjectTags']);
    Route::get('/project_tag/{slug}', [TagControllerV1::class, 'getProjectTagsBySlug']);
    Route::get('/version_tags', [TagControllerV1::class, 'getProjectVersionTags']);
    Route::get('/version_tag/{slug}', [TagControllerV1::class, 'getProjectVersionTagBySlug']);

    // Projects
    Route::get('/projects', [ProjectControllerV1::class, 'getProjects']);
    Route::get('/project/{slug}', [ProjectControllerV1::class, 'getProjectBySlug']);
});
