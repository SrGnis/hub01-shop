<?php

use App\Http\Controllers\EmailChangeController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\PasswordChangeController;
use App\Livewire\Auth\AccountDeactivated;
use App\Livewire\Platform\Analytics;
use App\Livewire\Platform\Collections;
use App\Livewire\Platform\Dashboard;
use App\Livewire\Platform\NotificationsComingSoon;
use App\Livewire\Platform\Projects;
use App\Livewire\Page;
use App\Livewire\ProjectManager;
use App\Livewire\CollectionEdit;
use App\Livewire\CollectionShow;
use App\Livewire\ProjectSearch;
use App\Livewire\UserProfile;
use App\Livewire\UserProfileEdit;
use App\Livewire\ProjectShow;
use App\Livewire\ProjectVersionForm;
use App\Livewire\ProjectVersionShow;
use App\Livewire\Welcome;
use Illuminate\Support\Facades\Route;

require __DIR__.'/admin.php';
require __DIR__.'/api_docs.php';

// Homepage
Route::livewire('/', Welcome::class)->name('welcome');

// Dynamic Pages
Route::livewire('/pages/{pageName}', Page::class)
    ->name('page.show');

// Account Deactivated Page
Route::livewire('/account/deactivated', AccountDeactivated::class)
    ->name('account.deactivated')
    ->withoutMiddleware(\App\Http\Middleware\EnsureUserIsNotDeactivated::class);

// User Profile
Route::livewire('/user/{user}', UserProfile::class)->name('user.profile');

// Collections
Route::livewire('/collection/hidden/{token}', CollectionShow::class)->name('collection.hidden.show');
Route::livewire('/collection/{collection}', CollectionShow::class)->name('collection.show');

Route::middleware(['auth', 'verified'])->group(function () {
    // User Profile Edit
    Route::livewire('/profile/edit', UserProfileEdit::class)->middleware('auth')->name('user.profile.edit');

    // Email Change Routes
    Route::get('/email-change/authorize/{token}', [EmailChangeController::class, 'authorize'])->name('email-change.authorize');
    Route::get('/email-change/verify/{token}', [EmailChangeController::class, 'verify'])->name('email-change.verify');

    // Password Change Routes
    Route::get('/password-change/verify/{token}', [PasswordChangeController::class, 'verify'])->name('password-change.verify');

    // Membership Management
    Route::get('/membership/{membership}/accept', [MembershipController::class, 'accept'])
        ->middleware('signed')
        ->name('membership.accept');

    Route::get('/membership/{membership}/reject', [MembershipController::class, 'reject'])
        ->middleware('signed')
        ->name('membership.reject');

    // Collection Management
    Route::livewire('/collection/{collection}/edit', CollectionEdit::class)->name('collection.edit');

    // Dashboard
    Route::livewire('/dashboard', Dashboard::class)->name('platform.dashboard');
    Route::livewire('/dashboard/notifications', NotificationsComingSoon::class)->name('platform.notifications');
    Route::livewire('/dashboard/collections', Collections::class)->name('platform.collections');
    Route::livewire('/dashboard/projects', Projects::class)->name('platform.projects');
    Route::livewire('/dashboard/analytics', Analytics::class)->name('platform.analytics');

});


Route::livewire('/search/{projectType}s', ProjectSearch::class)->name('project-search');

// Dummy route to use in the project-form component
Route::get('/{projectType}/', function () {
    return redirect(route('project-search', ['projectType' => request()->route('projectType')]));
})->name('dummy.project.show');
Route::livewire('/{projectType}/{project}', ProjectShow::class)->name('project.show');

// Project Management
Route::middleware(['auth','verified'])->group(function () {
    Route::livewire('/{projectType}/{project}/manage/{section?}', ProjectManager::class)
        ->name('project.manage')
        ->where('section', 'general|description|tags|links|versions|members|analytics|danger');
    Route::livewire('/{projectType}/{project}/version/create', ProjectVersionForm::class)->name('project.version.create');
});

// Project Version Management
Route::livewire('/{projectType}/{project}/version/{version_key}', ProjectVersionShow::class)->name('project.version.show');
Route::livewire('/{projectType}/{project}/version/{version_key}/edit', ProjectVersionForm::class)->name('project.version.edit')->middleware(['auth','verified']);

// File Downloads
Route::get('/{projectType}/{project}/version/{version}/file/{file}', [FileDownloadController::class, 'download'])
    ->name('file.download');
