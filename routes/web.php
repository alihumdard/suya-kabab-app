<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\SettingsController;

// Public Routes
Route::get('/', function () {
    return redirect()->route('admin.login');
})->name('home');

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    Route::view('login', 'pages.auth.login')->name('admin.login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Password reset routes
    Route::view('password/reset', 'pages.auth.password.email')->name('admin.password.request');
    Route::post('password/email', [AdminAuthController::class, 'sendPasswordReset'])->name('admin.password.email');
    Route::view('password/reset/{token}', 'pages.auth.password.reset')->name('admin.password.reset');
    Route::post('password/reset', [AdminAuthController::class, 'resetPassword'])->name('admin.password.update');

    // Registration routes (if needed)
    Route::view('register', 'pages.auth.signup')->name('admin.register');
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::view('register/otp/{email}', 'pages.auth.password.otp')->name('admin.register.otp.show');
    Route::post('register/otp/verify', [AdminAuthController::class, 'verifyOTP'])->name('admin.register.otp.verify');

    // Protected Admin Routes
    Route::middleware(['admin'])->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // Admin Pages
        Route::get('orders', function () {
            return view('pages.admin.orders');
        })->name('admin.orders');

        Route::get('menu', function () {
            return view('pages.admin.menu');
        })->name('admin.menu');

        Route::get('product', function () {
            return view('pages.admin.product');
        })->name('admin.product');

        Route::get('category', function () {
            return view('pages.admin.category');
        })->name('admin.category');

        // Admin Components
        Route::get('form', function () {
            return view('pages.admin.components.form');
        })->name('admin.form');

        Route::get('table', function () {
            return view('pages.admin.components.table');
        })->name('admin.table');

        Route::get('card', function () {
            return view('pages.admin.components.card');
        })->name('admin.card');

        // Settings Management Routes
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('admin.settings.index');
            Route::put('/', [SettingsController::class, 'update'])->name('admin.settings.update');
            Route::put('/{key}', [SettingsController::class, 'updateSingle'])->name('admin.settings.update.single');
            Route::get('/delivery', [SettingsController::class, 'getDeliverySettings'])->name('admin.settings.delivery');
            Route::put('/delivery', [SettingsController::class, 'updateDeliverySettings'])->name('admin.settings.delivery.update');
        });

    });
});

// Protected standalone dashboard route (for backward compatibility)
Route::get('/dashboard', function () {
    return view('pages.admin.dashboard');
})->name('dashboard')->middleware('admin');


