<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | OFFLINE PASSWORD RECOVERY
    |--------------------------------------------------------------------------
    | Employees request a reset and receive a one-day, one-time code only
    | after administrator approval. Administrators use one of their 10
    | recovery codes instead of requesting approval from themselves.
    */

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('forgot-password/verify-code', [PasswordResetLinkController::class, 'verifyForm'])
        ->name('password.code.form');

    Route::post('forgot-password/verify-code', [PasswordResetLinkController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('password.code.verify');

    Route::get('reset-password', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('password.store');

    Route::get('password/new-recovery-codes', [NewPasswordController::class, 'recoveryCodes'])
        ->name('password.recovery-codes');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
