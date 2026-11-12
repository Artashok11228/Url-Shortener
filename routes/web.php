<?php

declare(strict_types=1);

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/{code}', RedirectController::class)
    ->where('code', '[A-Za-z0-9_-]+')
    ->name('links.redirect');
