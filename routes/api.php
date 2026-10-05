<?php

use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\FormFieldController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\YudisiumEventController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BeritaAcaraController;
use App\Http\Controllers\Kadep\KadepPengajuanController;
use App\Http\Controllers\Kaprodi\KaprodiPengajuanController;
use App\Http\Controllers\Manit\ManitPengajuanController;
use App\Http\Controllers\PengajuanKonfirmasiController;
use App\Http\Controllers\PengajuanYudisiumController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\StafAkademik\StafAkademikPengajuanController;
use App\Http\Controllers\TandaTanganController;
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
    Route::put('/pengajuan', [PengajuanYudisiumController::class, 'update']);

    // Konfirmasi Data Penilaian (Mahasiswa)
    Route::middleware('role:mahasiswa')->prefix('pengajuan/me')->group(function () {
        Route::get('/rekap', [PengajuanKonfirmasiController::class, 'rekap']);
        Route::patch('/konfirmasi', [PengajuanKonfirmasiController::class, 'konfirmasi']);
    });

    // Upload Dokumen
    Route::post('/uploads', [UploadController::class, 'store']);

    // Master Data — Prodi & Periode Yudisium
    Route::get('/program-studi', [ProgramStudiController::class, 'index']);
    Route::get('/yudisium-events', [YudisiumEventController::class, 'index']);
    Route::post('/yudisium-events', [YudisiumEventController::class, 'store']);
    Route::patch('/yudisium-events/{event}', [YudisiumEventController::class, 'update']);
    Route::patch('/yudisium-events/{event}/berita-acara', [BeritaAcaraController::class, 'update']);

    // Staf Akademik Routes
    Route::middleware('role:staf_akademik,super_admin')->prefix('staf-akademik')->group(function () {
        Route::get('/pengajuan', [StafAkademikPengajuanController::class, 'index']);
        Route::get('/pengajuan/{pengajuan}', [StafAkademikPengajuanController::class, 'show']);
        Route::put('/pengajuan/{pengajuan}/penilaian', [StafAkademikPengajuanController::class, 'storePenilaian']);
        Route::post('/pengajuan/{pengajuan}/kirim-konfirmasi', [StafAkademikPengajuanController::class, 'kirimKonfirmasi']);
        Route::post('/pengajuan/{pengajuan}/checklist', [StafAkademikPengajuanController::class, 'checklist']);
    });

    // Approval Kaprodi
    Route::middleware('role:kaprodi')->prefix('kaprodi')->group(function () {
        Route::get('/pengajuan', [KaprodiPengajuanController::class, 'index']);
        Route::get('/pengajuan/{pengajuan}', [KaprodiPengajuanController::class, 'show']);
        Route::patch('/pengajuan/{pengajuan}/approve', [KaprodiPengajuanController::class, 'approve']);
        Route::patch('/pengajuan/{pengajuan}/reject', [KaprodiPengajuanController::class, 'reject']);
    });

    // Approval Manit (paralel dengan Kadep)
    Route::middleware('role:manit')->prefix('manit')->group(function () {
        Route::get('/pengajuan', [ManitPengajuanController::class, 'index']);
        Route::get('/pengajuan/{pengajuan}/preview', [ManitPengajuanController::class, 'preview']);
        Route::patch('/pengajuan/{pengajuan}/approve', [ManitPengajuanController::class, 'approve']);
        Route::patch('/pengajuan/{pengajuan}/reject', [ManitPengajuanController::class, 'reject']);
    });

    // Approval Kadep (paralel dengan Manit)
    Route::middleware('role:kadep')->prefix('kadep')->group(function () {
        Route::get('/pengajuan', [KadepPengajuanController::class, 'index']);
        Route::get('/pengajuan/{pengajuan}/preview', [KadepPengajuanController::class, 'preview']);
        Route::patch('/pengajuan/{pengajuan}/approve', [KadepPengajuanController::class, 'approve']);
        Route::patch('/pengajuan/{pengajuan}/reject', [KadepPengajuanController::class, 'reject']);
    });

    // E-Signature (Kaprodi/Manit/Kadep)
    Route::middleware('role:kaprodi,manit,kadep')->group(function () {
        Route::post('/signature', [TandaTanganController::class, 'store']);
        Route::get('/signature/me', [TandaTanganController::class, 'me']);
        Route::put('/signature/me', [TandaTanganController::class, 'update']);
    });

    // Admin Routes (Sementara tanpa role middleware)
    Route::prefix('admin')->group(function () {
        Route::apiResource('forms', FormController::class);

        Route::get('forms/{form}/fields', [FormFieldController::class, 'index']);
        Route::post('forms/{form}/fields', [FormFieldController::class, 'store']);
        Route::patch('forms/{form}/fields/{field}', [FormFieldController::class, 'update']);
        Route::delete('forms/{form}/fields/{field}', [FormFieldController::class, 'destroy']);

        Route::apiResource('events', YudisiumEventController::class);
        Route::apiResource('users', AdminUserController::class)->except(['edit', 'create']);
        Route::apiResource('roles', RoleController::class)->only(['index']);
    });
});
