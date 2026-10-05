<?php

namespace App\Http\Controllers\StafAkademik;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataPenilaianRequest;
use App\Http\Resources\DataPenilaianResource;
use App\Jobs\KirimEmailKonfirmasiJob;
use App\Models\DataBeritaAcaraMahasiswa;
use App\Models\PengajuanYudisium;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StafAkademikPengajuanController extends Controller
{
    /**
     * Dashboard daftar mahasiswa dengan filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PengajuanYudisium::with(['user.programStudi', 'yudisiumEvent']);

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter program_studi_id dari users.program_studi_id mahasiswa
        if ($request->filled('program_studi_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('program_studi_id', $request->program_studi_id);
            });
        }

        // Filter tanggal pengajuan (submitted_at)
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('submitted_at', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('submitted_at', '<=', $request->tanggal_sampai);
        }

        // Filter email_status
        if ($request->filled('email_status')) {
            $query->where('email_status', $request->email_status);
        }

        $pengajuan = $query->paginate($request->integer('limit', 15));

        return response()->json([
            'status' => 'success',
            'data' => $pengajuan,
        ]);
    }

    /**
     * Detail pengajuan mahasiswa.
     */
    public function show(PengajuanYudisium $pengajuan): JsonResponse
    {
        $pengajuan->load([
            'user.programStudi',
            'yudisiumEvent',
            'fieldValues.formField',
            'dataBeritaAcara',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $pengajuan,
        ]);
    }

    /**
     * Input/update 9 data penilaian (upsert).
     */
    public function storePenilaian(
        StoreDataPenilaianRequest $request,
        PengajuanYudisium $pengajuan
    ): JsonResponse {
        // Validasi status
        $allowedStatuses = ['submitted', 'checking_akademik', 'student_revision_requested'];
        if (!in_array($pengajuan->status, $allowedStatuses)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan dengan status '.$pengajuan->status.' tidak dapat diubah datanya.',
            ], 409);
        }

        $dataBeritaAcara = DB::transaction(function () use ($request, $pengajuan) {
            // Update status jika dari submitted -> checking_akademik
            if ($pengajuan->status === 'submitted') {
                $pengajuan->update(['status' => 'checking_akademik']);
            }

            // Upsert data penilaian
            return DataBeritaAcaraMahasiswa::updateOrCreate(
                ['pengajuan_id' => $pengajuan->id],
                $request->validated()
            );
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data penilaian berhasil disimpan',
            'data' => new DataPenilaianResource($dataBeritaAcara),
        ]);
    }

    /**
     * Kirim email konfirmasi ke mahasiswa (kirim & kirim ulang).
     */
    public function kirimKonfirmasi(PengajuanYudisium $pengajuan): JsonResponse
    {
        // Validasi sudah ada data penilaian
        if (!$pengajuan->dataBeritaAcara) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data penilaian belum diinput, tidak dapat mengirim konfirmasi.',
            ], 422);
        }

        // Validasi status
        $allowedStatuses = ['checking_akademik', 'student_revision_requested', 'waiting_student_confirmation'];
        if (!in_array($pengajuan->status, $allowedStatuses)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan dengan status '.$pengajuan->status.' tidak dapat dikirim konfirmasi.',
            ], 409);
        }

        // Validasi email sedang dalam proses pengiriman
        if ($pengajuan->email_status === 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Email sedang dalam proses pengiriman, mohon tunggu.',
            ], 409);
        }

        // Set status email pending dan increment attempts
        $pengajuan->update([
            'email_status' => 'pending',
            'email_error' => null,
            'email_attempts' => $pengajuan->email_attempts + 1,
        ]);

        // Dispatch job ke queue
        KirimEmailKonfirmasiJob::dispatch($pengajuan);

        return response()->json([
            'status' => 'success',
            'message' => 'Email konfirmasi sedang dikirim ke '.$pengajuan->user->email,
            'data' => [
                'email_status' => $pengajuan->email_status,
                'email_attempts' => $pengajuan->email_attempts,
            ],
        ], 202);
    }

    /**
     * Checklist akhir & routing ke Kaprodi.
     */
    public function checklist(Request $request, PengajuanYudisium $pengajuan): JsonResponse
    {
        // Validasi status
        if ($pengajuan->status !== 'confirmed_by_student') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pengajuan dengan status confirmed_by_student yang dapat di-checklist.',
            ], 409);
        }

        $pengajuan->update([
            'status' => 'final_checked_akademik',
            'checked_akademik_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan berhasil di-checklist dan dirouting ke Kaprodi',
            'data' => $pengajuan->fresh(['user', 'yudisiumEvent', 'checkedBy']),
        ]);
    }
}
