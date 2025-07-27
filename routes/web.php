<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AddonCategoryController;
use App\Http\Controllers\Admin\ProductAddonController;

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
    Route::get('password/otp/{email}', [AdminAuthController::class, 'showPasswordOTP'])->name('admin.password.otp.show');
    Route::post('password/otp/verify', [AdminAuthController::class, 'verifyPasswordOTP'])->name('admin.password.otp.verify');
    Route::get('password/reset/{email}/{otp}', [AdminAuthController::class, 'showPasswordReset'])->name('admin.password.reset');
    Route::post('password/reset', [AdminAuthController::class, 'resetPassword'])->name('admin.password.update');

    // Registration routes (if needed)
    Route::get('register', function () {
        return view('pages.auth.signup');
    })->name('admin.register.create');
    Route::post('register', [AdminAuthController::class, 'register'])->name('admin.register');
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

        // Product Management Routes
        Route::prefix('products')->name('admin.products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
            Route::put('/{product}', [ProductController::class, 'update'])->name('update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
        });

        // Category Management Routes
        Route::prefix('categories')->name('admin.categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        });

        // Backward compatibility route
        Route::get('category', [CategoryController::class, 'index'])->name('admin.category');
        Route::post('category', [CategoryController::class, 'store'])->name('admin.category.store');

        // Promotion Management Routes
        Route::prefix('promotions')->name('admin.promotions.')->group(function () {
            Route::get('/', [PromotionController::class, 'index'])->name('index');
            Route::post('/', [PromotionController::class, 'store'])->name('store');
            Route::get('/{promotion}/edit', [PromotionController::class, 'edit'])->name('edit');
            Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update');
            Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
            Route::patch('/{promotion}/toggle-status', [PromotionController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Addon Category Management Routes
        Route::prefix('addon_categories')->name('admin.addon_categories.')->group(function () {
            Route::get('/', [AddonCategoryController::class, 'index'])->name('index');
            Route::get('/create', [AddonCategoryController::class, 'create'])->name('create');
            Route::post('/', [AddonCategoryController::class, 'store'])->name('store');
            Route::get('/{addon_category}', [AddonCategoryController::class, 'show'])->name('show');
            Route::get('/{addon_category}/edit', [AddonCategoryController::class, 'edit'])->name('edit');
            Route::put('/{addon_category}', [AddonCategoryController::class, 'update'])->name('update');
            Route::delete('/{addon_category}', [AddonCategoryController::class, 'destroy'])->name('destroy');
        });

        // Product Addon Management Routes
        Route::prefix('product_addons')->name('admin.product_addons.')->group(function () {
            Route::get('/', [ProductAddonController::class, 'index'])->name('index');
            Route::get('/create', [ProductAddonController::class, 'create'])->name('create');
            Route::post('/', [ProductAddonController::class, 'store'])->name('store');
            Route::get('/{product_addon}', [ProductAddonController::class, 'show'])->name('show');
            Route::get('/{product_addon}/edit', [ProductAddonController::class, 'edit'])->name('edit');
            Route::put('/{product_addon}', [ProductAddonController::class, 'update'])->name('update');
            Route::delete('/{product_addon}', [ProductAddonController::class, 'destroy'])->name('destroy');
        });

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

        Route::get('delete-modal', function () {
            return view('pages.admin.components.delete-modal-demo');
        })->name('admin.delete-modal');

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


