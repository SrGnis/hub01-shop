<?php

use App\Livewire\Welcome;
use Illuminate\Support\Facades\Route;

require __DIR__.'/admin.php';

Route::get('/', function () {
    return view('welcome');
});

// // User Profile
// Route::get('/user/{user}', UserProfile::class)->name('user.profile');
// Route::get('/user/{user}/edit', UserProfileEdit::class)->middleware('auth')->name('user.profile.edit');

// // Membership Management
// Route::get('/membership/{membership}/accept', MembershipController::class.'@accept')
//     ->middleware('signed')
//     ->name('membership.accept');

// Route::get('/membership/{membership}/reject', MembershipController::class.'@reject')
//     ->middleware('signed')
//     ->name('membership.reject');

// Route::get('/create/{projectType}', ProjectForm::class)->middleware('verified')->name('project.create');
// Route::get('/search/{projectType}s', ProjectSearch::class)->name('project-search');
// Route::get('/{projectType}/{project}', ProjectShow::class)->name('project.show');
// Route::get('/{projectType}/{project}/edit', ProjectForm::class)->middleware('verified')->name('project.edit');
// Route::get('/{projectType}/{project}/version/create', ProjectVersionForm::class)->middleware('verified')->name('project.version.create');
// Route::get('/{projectType}/{project}/version/{version_key}', ProjectVersionShow::class)->name('project.version.show');
// Route::get('/{projectType}/{project}/version/{version_key}/edit', ProjectVersionForm::class)->middleware('verified')->name('project.version.edit');

// // File Downloads
// Route::get('/{projectType}/{project}/version/{version}/file/{file}', [FileDownloadController::class, 'download'])
//     ->name('file.download');

Route::get('/search/{projectType}s',function () {
    return view('welcome');
} )->name('project-search');