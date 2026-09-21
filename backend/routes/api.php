<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChangeRequestController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductionController;
use App\Http\Controllers\Api\QcDefectController;
use App\Http\Controllers\Api\QcInspectionController;
use App\Http\Controllers\Api\SpecificationController;
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

// Customer Progress Portal (Public Tracking - Phase 6)
Route::prefix('public')->name('api.v1.public.')->middleware(['throttle:60,1'])->group(function () {
    Route::get('/orders/{public_token}', [CustomerPortalController::class, 'show'])->name('orders.show');
});

// Authentication Routes
Route::prefix('auth')->name('api.v1.auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware(['auth:sanctum', 'workshop.context'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});

// Protected Business Routes
Route::middleware(['auth:sanctum', 'workshop.context'])->group(function () {
    // Customers CRUD (Phase 3)
    Route::apiResource('customers', CustomerController::class)->names([
        'index' => 'api.v1.customers.index',
        'store' => 'api.v1.customers.store',
        'show' => 'api.v1.customers.show',
        'update' => 'api.v1.customers.update',
        'destroy' => 'api.v1.customers.destroy',
    ]);

    // Orders Management (Phase 3)
    Route::prefix('orders')->name('api.v1.orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::patch('/{id}/status', [OrderController::class, 'changeStatus'])->name('change-status');
    });

    // Specifications (Phase 4)
    Route::prefix('orders/{orderId}/items/{itemId}/specifications')->name('api.v1.specifications.')->group(function () {
        Route::post('/', [SpecificationController::class, 'store'])->name('store');
        Route::get('/', [SpecificationController::class, 'index'])->name('index');
        Route::get('/current', [SpecificationController::class, 'current'])->name('current');
    });
    Route::prefix('specifications')->name('api.v1.specifications.')->group(function () {
        Route::get('/{id}', [SpecificationController::class, 'show'])->name('show');
        Route::patch('/{id}', [SpecificationController::class, 'update'])->name('update');
        Route::post('/{id}/lock', [SpecificationController::class, 'lock'])->name('lock');
    });

    // Change Requests (Phase 4)
    Route::prefix('orders/{orderId}/change-requests')->name('api.v1.change-requests.')->group(function () {
        Route::post('/', [ChangeRequestController::class, 'store'])->name('store');
        Route::get('/', [ChangeRequestController::class, 'index'])->name('index');
    });
    Route::prefix('change-requests')->name('api.v1.change-requests.')->group(function () {
        Route::get('/{id}', [ChangeRequestController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [ChangeRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [ChangeRequestController::class, 'reject'])->name('reject');
        Route::post('/{id}/cancel', [ChangeRequestController::class, 'cancel'])->name('cancel');
    });

    // Production Tracking & Media (Phase 4)
    Route::prefix('orders/{orderId}')->group(function () {
        Route::get('/production', [ProductionController::class, 'overview'])->name('api.v1.production.overview');
        Route::post('/production/init-stages', [ProductionController::class, 'initStages'])->name('api.v1.production.init-stages');
        Route::post('/production/stages', [ProductionController::class, 'storeStage'])->name('api.v1.production.stages.store');
        Route::get('/production-updates', [ProductionController::class, 'indexUpdates'])->name('api.v1.production.updates.index');
        Route::post('/media', [MediaController::class, 'store'])->name('api.v1.media.store');
        Route::get('/media', [MediaController::class, 'index'])->name('api.v1.media.index');
    });
    Route::prefix('production-stages')->name('api.v1.production-stages.')->group(function () {
        Route::patch('/{id}', [ProductionController::class, 'updateStageStatus'])->name('update-status');
        Route::post('/{id}/updates', [ProductionController::class, 'storeUpdate'])->name('store-update');
    });
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('api.v1.media.destroy');

    // Quality Control & Defect Tracking (Phase 5)
    Route::prefix('orders/{orderId}/qc-inspections')->name('api.v1.qc-inspections.')->group(function () {
        Route::get('/', [QcInspectionController::class, 'index'])->name('index');
        Route::post('/', [QcInspectionController::class, 'store'])->name('store');
    });
    Route::prefix('qc-inspections')->name('api.v1.qc-inspections.')->group(function () {
        Route::get('/{id}', [QcInspectionController::class, 'show'])->name('show');
        Route::post('/{id}/items', [QcInspectionController::class, 'evaluateItems'])->name('evaluate-items');
        Route::post('/{id}/finalize', [QcInspectionController::class, 'finalize'])->name('finalize');
        Route::post('/{id}/media', [QcInspectionController::class, 'uploadMedia'])->name('media');
        Route::post('/{id}/defects', [QcDefectController::class, 'store'])->name('defects.store');
    });
    Route::prefix('qc-defects')->name('api.v1.qc-defects.')->group(function () {
        Route::patch('/{id}/status', [QcDefectController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/media', [QcDefectController::class, 'uploadMedia'])->name('media');
        Route::delete('/{id}', [QcDefectController::class, 'destroy'])->name('destroy');
    });
});
