<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ProjectApprovalManagement;
use App\Livewire\Admin\ProjectManagement;
use App\Livewire\Admin\QuotaManagement;
use App\Livewire\Admin\SiteManagement;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\AbuseReportManagement;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('admin.dashboard');
    Route::get('/users', UserManagement::class)->name('admin.users');
    Route::get('/projects/index', ProjectManagement::class)->name('admin.projects.index');
    Route::get('/projects/approvals', ProjectApprovalManagement::class)->name('admin.projects.approvals');
    Route::get('/quotas', QuotaManagement::class)->name('admin.quotas');
    Route::get('/site', SiteManagement::class)->name('admin.site');
    Route::get('/abuse-reports', AbuseReportManagement::class)->name('admin.abuse-reports');
});

