<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpecialStockController;
use App\Http\Controllers\{AuthController, AuditLogController, BranchController, CategoryController, DashboardController, InventoryMovementController, LicenseController, ProductController, ReportController, StockReviewController, TransferController, UserController};

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
    // Kept for existing integrations; transfers are intentionally not exposed in the UI.
    Route::resource('transfers', TransferController::class)->only(['index', 'create', 'store']);
    Route::post('transfers/{transfer}/complete', [TransferController::class, 'complete'])->name('transfers.complete');
    Route::post('transfers/{transfer}/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');
    Route::middleware('role:administrador')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('licenses', [LicenseController::class, 'index'])->name('licenses.index');
        Route::post('licenses', [LicenseController::class, 'store'])->name('licenses.store');
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('stock-lists', SpecialStockController::class)->name('stock-lists.index');
    Route::get('reports/csv', [ReportController::class, 'csv'])->name('reports.csv');
    Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    Route::post('stock-reviews', [StockReviewController::class, 'store'])->name('stock-reviews.store');
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}/history', [ProductController::class, 'history'])->name('products.history');
});
Route::get('reports/shared/{branch}', [ReportController::class, 'sharedPdf'])->middleware('signed')->name('reports.shared');
