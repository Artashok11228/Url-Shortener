<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'create'])->name('login');
    Route::post('/login', [WebAuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/links/{link}/tags', [DashboardController::class, 'editTags'])->name('dashboard.links.tags.edit');
    Route::put('/dashboard/links/{link}/tags', [DashboardController::class, 'updateTags'])->name('dashboard.links.tags.update');
});

Route::get('/{code}', RedirectController::class)
    ->where('code', '[A-Za-z0-9_-]+')
    ->name('links.redirect');
