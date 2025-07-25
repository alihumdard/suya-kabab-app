<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Admin\SettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Suya Kabab API is working!',
        'timestamp' => now()
    ]);
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyEmail']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('resend-otp', [AuthController::class, 'resendOTP']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('profile/update', [AuthController::class, 'updateProfile']); // Changed to POST for multipart support
    });
});

// Public API Routes
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('products/{id}/customizations', [ProductController::class, 'customizations']);
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{id}', [CategoryController::class, 'show']);

// Promotion Routes
Route::apiResource('promotions', PromotionController::class);
Route::get('promotions/active/list', [PromotionController::class, 'active']);

// Note: User-specific favorites moved to protected routes section

// App Settings
Route::get('settings/delivery', [SettingsController::class, 'getDeliverySettings']);

// Protected API Routes
Route::middleware('auth:sanctum')->group(function () {
    // Home API Route (now under auth)
    Route::get('home', [HomeController::class, 'index']);

    // User Favorite Products Management (Protected)
    Route::get('user/favorites', [ProductController::class, 'favorites']);
    Route::patch('products/favorite/{id}', [ProductController::class, 'toggleFavorite']);



    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::post('orders/cancel/{id}', [OrderController::class, 'cancel']);

    // User profile route (alternative)
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()
            ]
        ]);
    });
});