<?php

use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\FormFieldController;
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

// Protected Routes (Butuh Header Authorization: Bearer {token})
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [GoogleAuthController::class, 'logout']);

    Route::get('/me', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->load('programStudi'),
        ]);
    });

    // Admin Routes (Sementara tanpa role middleware)
    Route::prefix('admin')->group(function () {
        // Form Management
        Route::apiResource('forms', FormController::class);

        // Form Field Management
        Route::get('forms/{form}/fields', [FormFieldController::class, 'index']);
        Route::post('forms/{form}/fields', [FormFieldController::class, 'store']);
        Route::patch('forms/{form}/fields/{field}', [FormFieldController::class, 'update']);
        Route::delete('forms/{form}/fields/{field}', [FormFieldController::class, 'destroy']);
    });
});
