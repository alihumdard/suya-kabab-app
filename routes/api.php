<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Example API routes
Route::prefix('v1')->group(function () {
    // Add your API routes here
    Route::get('/health', function () {
        return response()->json([
            'status' => 'OK',
            'message' => 'API is working!'
        ]);
    });
});