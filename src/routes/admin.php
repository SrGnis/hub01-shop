<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ProjectManagement;
use App\Livewire\Admin\SiteManagement;
use App\Livewire\Admin\UserManagement;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('admin.dashboard');
    Route::get('/users', UserManagement::class)->name('admin.users');
    Route::get('/projects', ProjectManagement::class)->name('admin.projects');
    Route::get('/site', SiteManagement::class)->name('admin.site');
});

