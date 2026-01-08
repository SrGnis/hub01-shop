<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup tasks to run daily at 2:00 AM
Schedule::command('cleanup:unverified-users --send-warnings --force')
    ->dailyAt('02:00')
    ->name('cleanup-unverified-users-warnings')
    ->withoutOverlapping();

Schedule::command('cleanup:unverified-users --delete --force')
    ->dailyAt('02:00')
    ->name('cleanup-unverified-users-delete')
    ->withoutOverlapping();

Schedule::command('cleanup:orphaned-files --force')
    ->dailyAt('02:00')
    ->name('cleanup-orphaned-files')
    ->withoutOverlapping();

// Schedule demo refresh to run every Friday at 1:00 AM
Schedule::command('demo:refresh')
    ->weeklyOn(5, '01:00')
    ->name('demo-refresh')
    ->withoutOverlapping();
