<?php

use App\Http\Controllers\Public\CustomerAuthController;
use App\Http\Controllers\Public\CustomerPasswordResetController;
use Illuminate\Support\Facades\Route;

Route::prefix('conta')->name('app.conta.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/login', [CustomerAuthController::class, 'login'])
            ->middleware('throttle:auth')
            ->name('login.store');
        Route::post('/register', [CustomerAuthController::class, 'register'])
            ->middleware('throttle:auth')
            ->name('register.store');
        Route::get('/forgot-password', [CustomerPasswordResetController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [CustomerPasswordResetController::class, 'store'])
            ->middleware('throttle:auth')
            ->name('password.email');
        Route::get('/reset-password/{token}', [CustomerPasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/reset-password', [CustomerPasswordResetController::class, 'update'])
            ->middleware('throttle:auth')
            ->name('password.store');
    });

    Route::middleware('auth:customer')->group(function () {
        Route::get('/', [CustomerAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
    });
});
