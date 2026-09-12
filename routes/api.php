<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Auth Routes
Route::prefix('auth/google')->group(function () {
    Route::get('/redirect', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
});

// Protected Routes (Butuh Header Header Authorization: Bearer {token})
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [GoogleAuthController::class, 'logout']);

    Route::get('/me', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->load('programStudi'),
        ]);
    });
});
