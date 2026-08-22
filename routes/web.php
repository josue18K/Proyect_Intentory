<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, AuditLogController, BranchController, CategoryController, DashboardController, InventoryMovementController, LicenseController, ProductController, ReportController, TransferController, UserController};

Route::get('/', fn() => auth()->check() ? redirect()->route('dashboard') : app(\App\Http\Controllers\AuthController::class)->create());
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::get('/registro', [AuthController::class, 'register'])->name('register');
    Route::post('/registro', [AuthController::class, 'registerStore'])->name('register.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::middleware('role:administrador')->group(function () {
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('branches', BranchController::class)->except('show');
        Route::resource('products', ProductController::class)->except(['show', 'index']);
    });
    Route::resource('movements', InventoryMovementController::class)->only(['index', 'create', 'store']);
    Route::resource('transfers', TransferController::class)->only(['index', 'create', 'store']);
    Route::post('transfers/{transfer}/complete', [TransferController::class, 'complete'])->name('transfers.complete');
    Route::post('transfers/{transfer}/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');
    Route::middleware('role:administrador')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::get('licenses', [LicenseController::class, 'index'])->name('licenses.index');
        Route::post('licenses', [LicenseController::class, 'store'])->name('licenses.store');
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/csv', [ReportController::class, 'csv'])->name('reports.csv');
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}/history', [ProductController::class, 'history'])->name('products.history');
});
