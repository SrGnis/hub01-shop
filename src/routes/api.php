<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TagController as TagControllerV1;
use App\Http\Controllers\Api\V1\ProjectController as ProjectControllerV1;
use App\Http\Controllers\Api\V1\ProjectVersionController as ProjectVersionControllerV1;
use App\Http\Controllers\Api\V1\UserController as UserControllerV1;

Route::prefix('v1')->group(function () {
    // Tags
    Route::get('/project_tags', [TagControllerV1::class, 'getProjectTags']);
    Route::get('/project_tag/{slug}', [TagControllerV1::class, 'getProjectTagsBySlug']);
    Route::get('/version_tags', [TagControllerV1::class, 'getProjectVersionTags']);
    Route::get('/version_tag/{slug}', [TagControllerV1::class, 'getProjectVersionTagBySlug']);

    // Project Types
    Route::get('/project_types', [ProjectControllerV1::class, 'getProjectTypes']);
    Route::get('/project_type/{slug}', [ProjectControllerV1::class, 'getProjectTypeBySlug']);

    // Projects
    Route::get('/projects', [ProjectControllerV1::class, 'getProjects']);
    Route::get('/project/{slug}', [ProjectControllerV1::class, 'getProjectBySlug']);

    // Project Versions
    Route::get('/project/{slug}/versions', [ProjectVersionControllerV1::class, 'getProjectVersions']);
    Route::get('/project/{slug}/version/{version}', [ProjectVersionControllerV1::class, 'getProjectVersionBySlug']);

    // User
    Route::get('/user/{name}', [UserControllerV1::class, 'getUserByName']);
    Route::get('/user/{name}/projects', [UserControllerV1::class, 'getUserProjects']);
});
