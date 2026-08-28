<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/products', [InventoryController::class, 'products']);
    Route::post('/products', [InventoryController::class, 'storeProduct'])->middleware('role:administrador');
    Route::put('/products/{product}', [InventoryController::class, 'updateProduct'])->middleware('role:administrador');
    Route::delete('/products/{product}', [InventoryController::class, 'deleteProduct'])->middleware('role:administrador');
    Route::get('/categories', [InventoryController::class, 'categories']);
    Route::post('/categories', [InventoryController::class, 'storeCategory'])->middleware('role:administrador');
    Route::put('/categories/{category}', [InventoryController::class, 'updateCategory'])->middleware('role:administrador');
    Route::delete('/categories/{category}', [InventoryController::class, 'deleteCategory'])->middleware('role:administrador');
    Route::get('/dashboard', [InventoryController::class, 'dashboard']);
    Route::get('/movements', [InventoryController::class, 'movements']);
    Route::post('/stock-reviews', [InventoryController::class, 'stockReview']);
    Route::get('/products/{product}/history', [InventoryController::class, 'productHistory']);
    Route::post('/movements', [InventoryController::class, 'storeMovement']);
    Route::get('/branches', [InventoryController::class, 'branches']);
    Route::get('/reports/inventory', [InventoryController::class, 'inventoryReport']);
    Route::middleware('role:administrador')->group(function () {
        Route::post('/branches', [InventoryController::class, 'storeBranch']);
        Route::put('/branches/{branch}', [InventoryController::class, 'updateBranch']);
        Route::delete('/branches/{branch}', [InventoryController::class, 'deleteBranch']);
        Route::get('/admin/users', [InventoryController::class, 'adminUsers']);
        Route::post('/admin/users', [InventoryController::class, 'storeUser']);
        Route::put('/admin/users/{user}', [InventoryController::class, 'updateUser']);
        Route::post('/admin/users/{user}/toggle', [InventoryController::class, 'toggleUser']);
        Route::delete('/admin/users/{user}', [InventoryController::class, 'deleteUser']);
        Route::get('/admin/licenses', [InventoryController::class, 'adminLicenses']);
        Route::post('/admin/licenses', [InventoryController::class, 'storeLicense']);
        Route::get('/admin/audit', [InventoryController::class, 'adminAudit']);
    });
});
