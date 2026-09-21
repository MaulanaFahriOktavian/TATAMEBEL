<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Base URL Prefix: /api/v1 (configured in bootstrap/app.php)
|
*/

// Health Check (Public)
Route::get('/health', HealthController::class)->name('api.v1.health');

// Authentication Routes
Route::prefix('auth')->name('api.v1.auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware(['auth:sanctum', 'workshop.context'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});

// Protected Business Routes (Phase 3: Customer & Order Management)
Route::middleware(['auth:sanctum', 'workshop.context'])->group(function () {
    // Customers CRUD
    Route::apiResource('customers', CustomerController::class)->names([
        'index' => 'api.v1.customers.index',
        'store' => 'api.v1.customers.store',
        'show' => 'api.v1.customers.show',
        'update' => 'api.v1.customers.update',
        'destroy' => 'api.v1.customers.destroy',
    ]);

    // Orders Management
    Route::prefix('orders')->name('api.v1.orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::patch('/{id}/status', [OrderController::class, 'changeStatus'])->name('change-status');
    });
});
