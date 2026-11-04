<?php

declare(strict_types=1);

use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('links', LinkController::class);
});
