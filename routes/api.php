<?php

use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\FormFieldController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\YudisiumEventController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BeritaAcaraPdfController;
use App\Http\Controllers\BeritaAcaraTemplateController;
use App\Http\Controllers\Kadep\KadepBeritaAcaraController;
use App\Http\Controllers\Kaprodi\KaprodiBeritaAcaraController;
use App\Http\Controllers\Manit\ManitBeritaAcaraController;
use App\Http\Controllers\PengajuanKonfirmasiController;
use App\Http\Controllers\PengajuanYudisiumController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\StafAkademik\StafAkademikBeritaAcaraController;
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

    // Profil User
    Route::get('/me', [ProfileController::class, 'show']);
    Route::match(['put', 'patch'], '/me', [ProfileController::class, 'update']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::match(['put', 'patch'], '/profile', [ProfileController::class, 'update']);
    Route::match(['put', 'patch'], '/user', [ProfileController::class, 'update']);

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

    // Berita Acara Preview & Export
    Route::get('/berita-acara/{beritaAcara}/preview', [BeritaAcaraPdfController::class, 'preview']);
    Route::get('/berita-acara/{beritaAcara}/export', [BeritaAcaraPdfController::class, 'export']);

    // Staf Akademik Routes
    Route::middleware('role:staf_akademik,super_admin')->prefix('staf-akademik')->group(function () {
        Route::get('/pengajuan', [StafAkademikPengajuanController::class, 'index']);
        Route::get('/pengajuan/{pengajuan}', [StafAkademikPengajuanController::class, 'show']);
        Route::put('/pengajuan/{pengajuan}/penilaian', [StafAkademikPengajuanController::class, 'storePenilaian']);
        Route::post('/pengajuan/{pengajuan}/kirim-konfirmasi', [StafAkademikPengajuanController::class, 'kirimKonfirmasi']);
        Route::post('/pengajuan/{pengajuan}/checklist', [StafAkademikPengajuanController::class, 'checklist']);

        Route::post('/berita-acara', [StafAkademikBeritaAcaraController::class, 'store']);
        Route::patch('/berita-acara/{beritaAcara}', [StafAkademikBeritaAcaraController::class, 'update']);
    });

    // Approval per berita acara: Kaprodi (prodi sendiri) -> Manit & Kadep (paralel)
    Route::middleware('role:kaprodi')->prefix('kaprodi')->group(function () {
        Route::get('/berita-acara', [KaprodiBeritaAcaraController::class, 'index']);
        Route::get('/berita-acara/{beritaAcara}', [KaprodiBeritaAcaraController::class, 'show']);
        Route::patch('/berita-acara/{beritaAcara}/approve', [KaprodiBeritaAcaraController::class, 'approve']);
        Route::patch('/berita-acara/{beritaAcara}/reject', [KaprodiBeritaAcaraController::class, 'reject']);
    });

    Route::middleware('role:manit')->prefix('manit')->group(function () {
        Route::get('/berita-acara', [ManitBeritaAcaraController::class, 'index']);
        Route::get('/berita-acara/{beritaAcara}', [ManitBeritaAcaraController::class, 'show']);
        Route::patch('/berita-acara/{beritaAcara}/approve', [ManitBeritaAcaraController::class, 'approve']);
        Route::patch('/berita-acara/{beritaAcara}/reject', [ManitBeritaAcaraController::class, 'reject']);
    });

    Route::middleware('role:kadep')->prefix('kadep')->group(function () {
        Route::get('/berita-acara', [KadepBeritaAcaraController::class, 'index']);
        Route::get('/berita-acara/{beritaAcara}', [KadepBeritaAcaraController::class, 'show']);
        Route::patch('/berita-acara/{beritaAcara}/approve', [KadepBeritaAcaraController::class, 'approve']);
        Route::patch('/berita-acara/{beritaAcara}/reject', [KadepBeritaAcaraController::class, 'reject']);
    });

    // E-Signature (Kaprodi/Manit/Kadep)
    Route::middleware('role:kaprodi,manit,kadep')->group(function () {
        Route::post('/signature', [TandaTanganController::class, 'store']);
        Route::get('/signature/me', [TandaTanganController::class, 'me']);
        Route::put('/signature/me', [TandaTanganController::class, 'update']);
    });

    // Admin Routes (Sementara tanpa role middleware)
    Route::prefix('admin')->group(function () {
        // Berita Acara Template (Super Admin only)
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/program-studi/{prodi}/berita-acara-template', [BeritaAcaraTemplateController::class, 'show']);
            Route::put('/program-studi/{prodi}/berita-acara-template', [BeritaAcaraTemplateController::class, 'update']);
        });

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
