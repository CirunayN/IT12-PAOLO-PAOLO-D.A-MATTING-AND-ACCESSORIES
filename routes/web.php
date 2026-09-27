<?php

use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
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

Route::middleware(['auth'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | SELF-SERVICE SETTINGS - ALL AUTHENTICATED USERS
    |--------------------------------------------------------------------------
    */
    Route::get('/settings/account', [AccountSettingsController::class, 'show'])
        ->name('settings.account');

    Route::post('/settings/account/email/send-code', [AccountSettingsController::class, 'sendEmailChangeCode'])
        ->middleware('throttle:3,1')
        ->name('settings.email.send');

    Route::get('/settings/account/email/verify', [AccountSettingsController::class, 'verifyEmailForm'])
        ->name('settings.email.verify.form');

    Route::post('/settings/account/email/verify', [AccountSettingsController::class, 'verifyEmail'])
        ->middleware('throttle:10,1')
        ->name('settings.email.verify');

    Route::post('/settings/account/email/resend', [AccountSettingsController::class, 'resendEmailChangeCode'])
        ->middleware('throttle:3,1')
        ->name('settings.email.resend');

    Route::post('/settings/account/email/cancel', [AccountSettingsController::class, 'cancelEmailChange'])
        ->name('settings.email.cancel');

    Route::view('/settings/password', 'settings.password')
        ->name('settings.password');

    /*
    |--------------------------------------------------------------------------
    | MY TRANSACTIONS - ALWAYS SCOPED TO THE LOGGED-IN USER
    |--------------------------------------------------------------------------
    */
    Route::get('/my-transactions', [TransactionController::class, 'index'])
        ->name('transactions.index');

    Route::get('/my-transactions/print', [TransactionController::class, 'print'])
        ->name('transactions.print');

    /*
    |--------------------------------------------------------------------------
    | POS - EMPLOYEE + ADMIN
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Admin,Cashier,Employee'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::get('/pos/receipt/{id}', [PosController::class, 'receipt'])
            ->whereNumber('id')
            ->name('pos.receipt');
    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN / OWNER ONLY
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        /* Inventory */
        Route::resource('products', ProductController::class)->except(['destroy']);
        Route::post('/categories', [CategoryController::class, 'store'])
            ->name('categories.store');

        Route::get('/stock-in', [StockInController::class, 'index'])
            ->name('stock-in.index');
        Route::get('/stock-in/create', [StockInController::class, 'create'])
            ->name('stock-in.create');
        Route::post('/stock-in', [StockInController::class, 'store'])
            ->name('stock-in.store');

        Route::delete('/products/{product}', [ProductController::class, 'destroy'])
            ->name('products.destroy');
        Route::post('/products/{product}/restore', [ProductController::class, 'restore'])
            ->name('products.restore');

        /* Reports */
        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');
        Route::get('/reports/print', [ReportController::class, 'print'])
            ->name('reports.print');

        /* Employee registration with Gmail confirmation */
        Route::get('/employees', [EmployeeController::class, 'index'])
            ->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])
            ->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('employees.store');
        Route::get('/employees/verify', [EmployeeController::class, 'verifyForm'])
            ->name('employees.verify.form');
        Route::post('/employees/verify', [EmployeeController::class, 'verify'])
            ->middleware('throttle:10,1')
            ->name('employees.verify');
        Route::post('/employees/resend-code', [EmployeeController::class, 'resend'])
            ->middleware('throttle:3,1')
            ->name('employees.resend');

        /* Backup */
        Route::get('/backup', [BackupController::class, 'index'])
            ->name('backup.index');
        Route::post('/backup/create', [BackupController::class, 'createBackup'])
            ->name('backup.create');
        Route::post('/backup/settings', [BackupController::class, 'updateSettings'])
            ->name('backup.settings');
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
