<?php

use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\FormFieldController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\YudisiumEventController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\PengajuanYudisiumController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\UploadController;
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

    // Pengajuan Yudisium (Mahasiswa)
    Route::get('/pengajuan/active-form', [PengajuanYudisiumController::class, 'activeForm']);
    Route::get('/pengajuan/me', [PengajuanYudisiumController::class, 'me']);
    Route::post('/pengajuan', [PengajuanYudisiumController::class, 'store']);

    // Upload Dokumen
    Route::post('/uploads', [UploadController::class, 'store']);

    // Master Data — Prodi & Periode Yudisium (sesuai dokumen Breakdown List API)
    Route::get('/program-studi', [ProgramStudiController::class, 'index']);
    Route::get('/yudisium-events', [YudisiumEventController::class, 'index']);
    Route::post('/yudisium-events', [YudisiumEventController::class, 'store']);
    Route::patch('/yudisium-events/{event}', [YudisiumEventController::class, 'update']);

    // Admin Routes (Sementara tanpa role middleware)
    Route::prefix('admin')->group(function () {
        // Form Management
        Route::apiResource('forms', FormController::class);

        // Form Field Management
        Route::get('forms/{form}/fields', [FormFieldController::class, 'index']);
        Route::post('forms/{form}/fields', [FormFieldController::class, 'store']);
        Route::patch('forms/{form}/fields/{field}', [FormFieldController::class, 'update']);
        Route::delete('forms/{form}/fields/{field}', [FormFieldController::class, 'destroy']);

        // Yudisium Event Management
        Route::apiResource('events', YudisiumEventController::class);

        // User Management
        Route::apiResource('users', AdminUserController::class)->except(['edit', 'create']);

        // Role Management
        Route::apiResource('roles', RoleController::class)->only(['index', 'store']);

    });
});
