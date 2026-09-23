<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS Terminal
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/pos/receipt/{id}', [PosController::class, 'receipt'])->name('pos.receipt');

    // Inventory / Products
    Route::post('/products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::resource('products', ProductController::class);

    // Stock-In Receiving
    Route::get('/stock-in', [StockInController::class, 'index'])->name('stock-in.index');
    Route::get('/stock-in/create', [StockInController::class, 'create'])->name('stock-in.create');
    Route::post('/stock-in', [StockInController::class, 'store'])->name('stock-in.store');

    // Backup & Restore
    Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
    Route::post('/backup/create', [BackupController::class, 'createBackup'])->name('backup.create');
    Route::post('/backup/settings', [BackupController::class, 'updateSettings'])->name('backup.settings');
    Route::get('/backup/download/{filename}', [BackupController::class, 'downloadBackup'])->name('backup.download');
    Route::post('/backup/delete', [BackupController::class, 'deleteBackup'])->name('backup.delete');
    Route::post('/backup/restore', [BackupController::class, 'restoreBackup'])->name('backup.restore');

    // Users & Staff
    Route::resource('users', UserController::class);

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';