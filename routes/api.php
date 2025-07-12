<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;

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
        Route::put('profile', [AuthController::class, 'updateProfile']);
    });
});

// Public API Routes
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{id}', [CategoryController::class, 'show']);

// Protected API Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('cart', CartController::class);
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);

    // Order review and calculation routes
    Route::get('orders/review', [OrderController::class, 'review']);
    Route::post('orders/calculate-total', [OrderController::class, 'calculateTotal']);
    Route::post('orders/validate-discount', [OrderController::class, 'validateDiscountCode']);

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