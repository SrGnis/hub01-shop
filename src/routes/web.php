<?php

use App\Livewire\Welcome;
use Illuminate\Support\Facades\Route;

Route::get('/', Welcome::class);

// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Welcome::class)->name('dashboard');
});
