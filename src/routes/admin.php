<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ProjectApprovalManagement;
use App\Livewire\Admin\ProjectManagement;
use App\Livewire\Admin\QuotaManagement;
use App\Livewire\Admin\SiteManagement;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\AbuseReportManagement;
use App\Livewire\Admin\NotificationManagement;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::livewire('/dashboard', Dashboard::class)->name('admin.dashboard');
    Route::livewire('/users', UserManagement::class)->name('admin.users');
    Route::livewire('/projects/index', ProjectManagement::class)->name('admin.projects.index');
    Route::livewire('/projects/approvals', ProjectApprovalManagement::class)->name('admin.projects.approvals');
    Route::livewire('/quotas', QuotaManagement::class)->name('admin.quotas');
    Route::livewire('/site', SiteManagement::class)->name('admin.site');
    Route::livewire('/abuse-reports', AbuseReportManagement::class)->name('admin.abuse-reports');
    Route::livewire('/notifications', NotificationManagement::class)->name('admin.notifications');
});

