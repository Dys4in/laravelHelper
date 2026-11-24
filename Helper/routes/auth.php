<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\FirstPasswordController;
use App\Http\Controllers\ManagedPasswordLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->middleware(['guest', 'no.cache'])
    ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::get('recuperar/{token}', [ManagedPasswordLinkController::class, 'show'])->middleware(['guest', 'no.cache', 'throttle:20,1'])->name('managed-password.show');
Route::post('recuperar/{token}', [ManagedPasswordLinkController::class, 'update'])->middleware(['guest', 'throttle:10,1'])->name('managed-password.update');

Route::middleware('auth')->group(function () {
    Route::get('primera-clave', [FirstPasswordController::class, 'edit'])->middleware('no.cache')->name('password.first.edit');
    Route::post('primera-clave', [FirstPasswordController::class, 'update'])->name('password.first.update');
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
