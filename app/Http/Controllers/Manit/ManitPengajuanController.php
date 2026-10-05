<?php

namespace App\Http\Controllers\Manit;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectPengajuanRequest;
use App\Models\PengajuanYudisium;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManitPengajuanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pengajuan = PengajuanYudisium::with(['user.programStudi', 'yudisiumEvent'])
            ->where('status', 'approved_kaprodi')
            ->whereNull('approved_manit_at')
            ->paginate($request->integer('limit', 15));

        return response()->json([
            'status' => 'success',
            'data' => $pengajuan,
        ]);
    }

    public function preview(PengajuanYudisium $pengajuan): JsonResponse
    {
        $pengajuan->load(['user.programStudi', 'yudisiumEvent', 'fieldValues.formField', 'dataBeritaAcara']);

        return response()->json([
            'status' => 'success',
            'data' => $pengajuan,
        ]);
    }

    public function approve(Request $request, PengajuanYudisium $pengajuan): JsonResponse
    {
        if ($pengajuan->status !== 'approved_kaprodi') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan dengan status '.$pengajuan->status.' tidak dapat di-approve Manit.',
            ], 409);
        }

        if ($pengajuan->approved_manit_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan ini sudah diproses Manit sebelumnya.',
            ], 409);
        }

        if (! $request->user()->tandaTangan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum mengupload e-signature. Upload dulu lewat POST /signature.',
            ], 422);
        }

        $pengajuan->update([
            'approved_manit_by' => $request->user()->id,
            'approved_manit_at' => now(),
            'status' => $pengajuan->approved_kadep_at ? 'completed' : 'approved_kaprodi',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Disetujui Manit'.($pengajuan->approved_kadep_at ? ', Berita Acara final' : ', menunggu approval Kadep'),
            'data' => $pengajuan->fresh(['user', 'yudisiumEvent', 'approvedManitBy', 'approvedKadepBy']),
        ]);
    }

    public function reject(RejectPengajuanRequest $request, PengajuanYudisium $pengajuan): JsonResponse
    {
        if ($pengajuan->status !== 'approved_kaprodi') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan dengan status '.$pengajuan->status.' tidak dapat direject Manit.',
            ], 409);
        }

        $pengajuan->update([
            'status' => 'checking_akademik',
            'catatan_revisi_internal' => $request->string('catatan_revisi_internal'),
            'approved_kaprodi_by' => null,
            'approved_kaprodi_at' => null,
            'approved_manit_by' => null,
            'approved_manit_at' => null,
            'approved_kadep_by' => null,
            'approved_kadep_at' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan dikembalikan ke Staf Akademik untuk direvisi',
            'data' => $pengajuan->fresh(['user', 'yudisiumEvent']),
        ]);
    }
}
