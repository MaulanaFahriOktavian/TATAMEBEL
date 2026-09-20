<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Base URL Prefix: /api/v1 (configured in bootstrap/app.php)
|
*/

Route::get('/health', HealthController::class)->name('api.v1.health');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
