<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;

// Public Routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Protected Admin Routes
    Route::middleware(['admin'])->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // Add more admin routes here as needed
        // Route::resource('products', AdminProductController::class);
        // Route::resource('categories', AdminCategoryController::class);
        // Route::resource('orders', AdminOrderController::class);
        // Route::resource('users', AdminUserController::class);
    });
});

// Dashboard
Route::get('/dashboard', function () {
    return view('pages.admin.dashboard');
})->name('dashboard');

// Orders
Route::get('/orders', function () {
    return view('pages.admin.orders');
})->name('orders');

// Menu
Route::get('/menu', function () {
    return view('pages.admin.menu');
})->name('menu');

// Product
Route::get('/product', function () {
    return view('pages.admin.product');
})->name('product');

// Category
Route::get('/category', function () {
    return view('pages.admin.category');
})->name('category');

// Components Group
Route::get('/form', function () {
    return view('pages.admin.components.form');
})->name('form');

Route::get('/table', function () {
    return view('pages.admin.components.table');
})->name('table');

Route::get('/card', function () {
    return view('pages.admin.components.card');
})->name('card');
