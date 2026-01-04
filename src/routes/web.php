<?php

use App\Http\Controllers\EmailChangeController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\PasswordChangeController;
use App\Livewire\Auth\AccountDeactivated;
use App\Livewire\Page;
use App\Livewire\ProjectForm;
use App\Livewire\ProjectSearch;
use App\Livewire\UserProfile;
use App\Livewire\UserProfileEdit;
use App\Livewire\ProjectShow;
use App\Livewire\ProjectVersionForm;
use App\Livewire\ProjectVersionShow;
use Illuminate\Support\Facades\Route;

require __DIR__.'/admin.php';

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Dynamic Pages
Route::get('/pages/{pageName}', Page::class)
    ->name('page.show');

// Account Deactivated Page
Route::get('/account/deactivated', AccountDeactivated::class)
    ->name('account.deactivated')
    ->withoutMiddleware(\App\Http\Middleware\EnsureUserIsNotDeactivated::class);

// User Profile
Route::get('/user/{user}', UserProfile::class)->name('user.profile');
Route::get('/profile/edit', UserProfileEdit::class)->middleware('auth')->name('user.profile.edit');

// Email Change Routes
Route::middleware('auth')->group(function () {
    Route::get('/email-change/authorize/{token}', [EmailChangeController::class, 'authorize'])->name('email-change.authorize');
    Route::get('/email-change/verify/{token}', [EmailChangeController::class, 'verify'])->name('email-change.verify');
});

// Password Change Routes
Route::middleware('auth')->group(function () {
    Route::get('/password-change/verify/{token}', [PasswordChangeController::class, 'verify'])->name('password-change.verify');
});

// Membership Management
Route::get('/membership/{membership}/accept', MembershipController::class.'@accept')
    ->middleware('signed')
    ->name('membership.accept');

Route::get('/membership/{membership}/reject', MembershipController::class.'@reject')
    ->middleware('signed')
    ->name('membership.reject');

Route::get('/create/{projectType}', ProjectForm::class)->middleware('verified')->name('project.create');
Route::get('/search/{projectType}s', ProjectSearch::class)->name('project-search');
// Dummy route to use in the project-form component
Route::get('/{projectType}/', function () {
    return redirect(route('project-search', ['projectType' => request()->route('projectType')]));
})->name('dummy.project.show');
Route::get('/{projectType}/{project}', ProjectShow::class)->name('project.show');
Route::get('/{projectType}/{project}/edit', ProjectForm::class)->middleware('verified')->name('project.edit');
Route::get('/{projectType}/{project}/version/create', ProjectVersionForm::class)->middleware('verified')->name('project.version.create');
Route::get('/{projectType}/{project}/version/{version_key}', ProjectVersionShow::class)->name('project.version.show');
Route::get('/{projectType}/{project}/version/{version_key}/edit', ProjectVersionForm::class)->middleware('verified')->name('project.version.edit');

// File Downloads
Route::get('/{projectType}/{project}/version/{version}/file/{file}', [FileDownloadController::class, 'download'])
    ->name('file.download');
