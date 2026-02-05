<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TokenTestController;
use App\Http\Controllers\Api\V1\TagController as TagControllerV1;
use App\Http\Controllers\Api\V1\ProjectController as ProjectControllerV1;
use App\Http\Controllers\Api\V1\ProjectVersionController as ProjectVersionControllerV1;
use App\Http\Controllers\Api\V1\UserController as UserControllerV1;

// Token Test Endpoint (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/test-token', TokenTestController::class)->name('api.token.test');
});

Route::prefix('v1')->group(function () {
    // Tags
    Route::get('/project_tags', [TagControllerV1::class, 'getProjectTags'])->name('api.v1.project_tags');
    Route::get('/project_tag/{slug}', [TagControllerV1::class, 'getProjectTagsBySlug'])->name('api.v1.project_tag');
    Route::get('/version_tags', [TagControllerV1::class, 'getProjectVersionTags'])->name('api.v1.version_tags');
    Route::get('/version_tag/{slug}', [TagControllerV1::class, 'getProjectVersionTagBySlug'])->name('api.v1.version_tag');

    // Project Types
    Route::get('/project_types', [ProjectControllerV1::class, 'getProjectTypes'])->name('api.v1.project_types');
    Route::get('/project_type/{slug}', [ProjectControllerV1::class, 'getProjectTypeBySlug'])->name('api.v1.project_type');

    // Projects
    Route::get('/projects', [ProjectControllerV1::class, 'getProjects'])->name('api.v1.projects');
    Route::get('/project/{slug}', [ProjectControllerV1::class, 'getProjectBySlug'])->name('api.v1.project');

    // Project Versions
    Route::get('/project/{slug}/versions', [ProjectVersionControllerV1::class, 'getProjectVersions'])->name('api.v1.project_versions');
    Route::get('/project/{slug}/version/{version}', [ProjectVersionControllerV1::class, 'getProjectVersionBySlug'])->name('api.v1.project_version');

    // Project Versions (Authenticated)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/project/{slug}/versions', [ProjectVersionControllerV1::class, 'store'])->name('api.v1.project_version.store');

        /*
         * Laravel don't support PUT with multipart, so we use POST
         * A posible solution is to use _method=PUT but I see it more complex than just use POST
         * Solution reference: https://github.com/laravel/framework/issues/13457#issuecomment-239451567
        */
        Route::post('/project/{slug}/version/{version}', [ProjectVersionControllerV1::class, 'update'])->name('api.v1.project_version.update');
        Route::delete('/project/{slug}/version/{version}', [ProjectVersionControllerV1::class, 'destroy'])->name('api.v1.project_version.destroy');
    });

    // User
    Route::get('/user/{name}', [UserControllerV1::class, 'getUserByName'])->name('api.v1.user');
    Route::get('/user/{name}/projects', [UserControllerV1::class, 'getUserProjects'])->name('api.v1.user.projects');
});
