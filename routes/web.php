<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockInController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }
    return auth()->user()->isAdmin() ? redirect()->route('dashboard') : redirect()->route('pos.index');
});

Route::middleware(['auth'])->group(function () {
    // -------------------------------------------------------------
    // Common Authenticated User Routes
    // -------------------------------------------------------------
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // -------------------------------------------------------------
    // Operational Tier (Owner / Admin & Employee / Cashier)
    // -------------------------------------------------------------
    Route::middleware(['role:Admin,Cashier'])->group(function () {
        // POS Terminal
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::get('/pos/receipt/{id}', [PosController::class, 'receipt'])->name('pos.receipt');

        // Inventory & Restock
        Route::resource('products', ProductController::class)->except(['destroy']);
        Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
        Route::get('/stock-in', [StockInController::class, 'index'])->name('stock-in.index');
        Route::get('/stock-in/create', [StockInController::class, 'create'])->name('stock-in.create');
        Route::post('/stock-in', [StockInController::class, 'store'])->name('stock-in.store');
    });

    // -------------------------------------------------------------
    // Owner Level Only (Strictly Admin / Owner Role)
    // -------------------------------------------------------------
    Route::middleware(['role:Admin'])->group(function () {
        // Executive Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Database Backup & Recovery
        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup/create', [BackupController::class, 'createBackup'])->name('backup.create');
        Route::post('/backup/settings', [BackupController::class, 'updateSettings'])->name('backup.settings');
        Route::get('/backup/download/{filename}', [BackupController::class, 'downloadBackup'])->name('backup.download');
        Route::post('/backup/delete', [BackupController::class, 'deleteBackup'])->name('backup.delete');
        Route::post('/backup/restore', [BackupController::class, 'restoreBackup'])->name('backup.restore');

        // Destructive Inventory Operations
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
    });
});

require __DIR__.'/auth.php';