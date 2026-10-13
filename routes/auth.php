<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RecoveryCodeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [RecoveryCodeController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [RecoveryCodeController::class, 'verify'])
        ->middleware('throttle:5,1')
        ->name('password.recovery.verify');

    Route::get('reset-password', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('password.store');

    Route::get('password/recovery-code', [RecoveryCodeController::class, 'showReplacement'])
        ->name('password.recovery.replacement');

});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('password/recovery-code', [RecoveryCodeController::class, 'generate'])
        ->middleware('throttle:3,1')->name('password.recovery.generate');
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
