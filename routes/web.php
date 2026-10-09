<?php

use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->isAdmin()
        ? redirect()->route('dashboard')
        : redirect()->route('pos.index');
});

Route::post('/recovery-codes/download', [\App\Http\Controllers\RecoveryCodeExportController::class, 'download'])
    ->middleware('throttle:10,1')->name('recovery-codes.download');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/settings/account', [AccountSettingsController::class, 'show'])
        ->name('settings.account');

    Route::view('/settings/password', 'settings.password')
        ->name('settings.password');

    Route::get('/my-transactions', [TransactionController::class, 'index'])
        ->name('transactions.index');

    Route::get('/my-transactions/print', [TransactionController::class, 'print'])
        ->name('transactions.print');

    Route::get('/my-transactions/export-csv', [TransactionController::class, 'exportCsv'])
        ->name('transactions.export_csv');

    Route::middleware(['role:Admin,Cashier,Employee'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::get('/pos/receipt/{id}', [PosController::class, 'receipt'])
            ->whereNumber('id')
            ->name('pos.receipt');
    });

    Route::middleware(['role:Admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/security', [SecurityController::class, 'index'])
            ->name('security.index');

        Route::post(
            '/security/password-reset/{passwordResetRequest}/approve',
            [SecurityController::class, 'approveReset']
        )->name('security.reset.approve');

        Route::delete(
            '/security/password-reset/{passwordResetRequest}',
            [SecurityController::class, 'denyReset']
        )->name('security.reset.deny');

        Route::post(
            '/security/users',
            [SecurityController::class, 'storeUser']
        )->middleware('throttle:5,1')
            ->name('security.users.store');

        Route::patch(
            '/security/users/{user}/access',
            [SecurityController::class, 'toggleUser']
        )->name('security.users.access');

        Route::post(
            '/security/recovery-codes/regenerate',
            [SecurityController::class, 'regenerateRecoveryCodes']
        )->middleware('throttle:3,1')
            ->name('security.recovery.regenerate');

        Route::get('/products/print', [ProductController::class, 'print'])->name('products.print');

        Route::resource('products', ProductController::class)->except(['destroy']);

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->whereNumber('category')->name('categories.update');
        Route::post('/categories/{category}/archive', [CategoryController::class, 'archive'])->whereNumber('category')->name('categories.archive');
        Route::post('/categories/{category}/restore', [CategoryController::class, 'restore'])->whereNumber('category')->name('categories.restore');
        Route::post('/categories', [CategoryController::class, 'store'])
            ->name('categories.store');

        Route::get('/stock-in', [StockInController::class, 'index'])
            ->name('stock-in.index');

        Route::get('/stock-in/print', [StockInController::class, 'print'])->name('stock-in.print');

        Route::get('/stock-in/create', [StockInController::class, 'create'])
            ->name('stock-in.create');

        Route::post('/stock-in', [StockInController::class, 'store'])
            ->name('stock-in.store');

        Route::delete('/products/{product}', [ProductController::class, 'destroy'])
            ->name('products.destroy');

        Route::post('/products/{product}/restore', [ProductController::class, 'restore'])
            ->name('products.restore');

        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/reports/print', [ReportController::class, 'print'])
            ->name('reports.print');

        Route::get('/backup', [BackupController::class, 'index'])
            ->name('backup.index');

        Route::post('/backup/create', [BackupController::class, 'createBackup'])
            ->name('backup.create');

        Route::post('/backup/settings', [BackupController::class, 'updateSettings'])
            ->name('backup.settings');

        Route::post('/backup/folder-picker', [BackupController::class, 'pickFolder'])->name('backup.folder-picker');

        Route::get('/backup/folders', [BackupController::class, 'browseFolders'])
            ->name('backup.folders');

        Route::get('/backup/download/{filename}', [BackupController::class, 'downloadBackup'])
            ->name('backup.download');

        Route::post('/backup/delete', [BackupController::class, 'deleteBackup'])
            ->name('backup.delete');

        Route::post('/backup/recover', [BackupController::class, 'recoverBackup'])
            ->name('backup.recover');

        Route::post('/backup/purge', [BackupController::class, 'purgeBackup'])
            ->name('backup.purge');

        Route::post('/backup/restore', [BackupController::class, 'restoreBackup'])
            ->name('backup.restore');
    });
});

require __DIR__ . '/auth.php';
