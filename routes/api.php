<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/products', [InventoryController::class, 'products']);
    Route::get('/dashboard', [InventoryController::class, 'dashboard']);
    Route::get('/movements', [InventoryController::class, 'movements']);
    Route::post('/movements', [InventoryController::class, 'storeMovement']);
    Route::get('/branches', [InventoryController::class, 'branches']);
});
