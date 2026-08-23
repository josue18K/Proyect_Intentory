<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/products', [InventoryController::class, 'products']);
    Route::get('/dashboard', [InventoryController::class, 'dashboard']);
    Route::get('/movements', [InventoryController::class, 'movements']);
    Route::post('/movements', [InventoryController::class, 'storeMovement']);
    Route::get('/branches', [InventoryController::class, 'branches']);
    Route::get('/transfers', [InventoryController::class, 'transfers']);
    Route::post('/transfers', [InventoryController::class, 'storeTransfer']);
    Route::post('/transfers/{transfer}/complete', [InventoryController::class, 'completeTransfer']);
    Route::get('/reports/inventory', [InventoryController::class, 'inventoryReport']);
    Route::middleware('role:administrador')->group(function () {
        Route::get('/admin/users', [InventoryController::class, 'adminUsers']);
        Route::get('/admin/licenses', [InventoryController::class, 'adminLicenses']);
        Route::post('/admin/licenses', [InventoryController::class, 'storeLicense']);
        Route::get('/admin/audit', [InventoryController::class, 'adminAudit']);
    });
});
