<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectPengajuanRequest;
use App\Models\PengajuanYudisium;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KaprodiPengajuanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pengajuan = PengajuanYudisium::with(['user.programStudi', 'yudisiumEvent'])
            ->where('status', 'final_checked_akademik')
            ->whereHas('user', function ($q) use ($request) {
                $q->where('program_studi_id', $request->user()->prodiDipimpin?->id);
            })
            ->paginate($request->integer('limit', 15));

        return response()->json([
            'status' => 'success',
            'data' => $pengajuan,
        ]);
    }

    public function show(Request $request, PengajuanYudisium $pengajuan): JsonResponse
    {
        $this->authorizeProdi($request, $pengajuan);

        $pengajuan->load(['user.programStudi', 'yudisiumEvent', 'fieldValues.formField', 'dataBeritaAcara']);

        return response()->json([
            'status' => 'success',
            'data' => $pengajuan,
        ]);
    }

    public function approve(Request $request, PengajuanYudisium $pengajuan): JsonResponse
    {
        $this->authorizeProdi($request, $pengajuan);

        if ($pengajuan->status !== 'final_checked_akademik') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan dengan status '.$pengajuan->status.' tidak dapat di-approve Kaprodi.',
            ], 409);
        }

        if (! $request->user()->tandaTangan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum mengupload e-signature. Upload dulu lewat POST /signature.',
            ], 422);
        }

        $pengajuan->update([
            'status' => 'approved_kaprodi',
            'approved_kaprodi_by' => $request->user()->id,
            'approved_kaprodi_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan disetujui, diteruskan paralel ke Manit & Kadep',
            'data' => $pengajuan->fresh(['user', 'yudisiumEvent', 'approvedKaprodiBy']),
        ]);
    }

    public function reject(RejectPengajuanRequest $request, PengajuanYudisium $pengajuan): JsonResponse
    {
        $this->authorizeProdi($request, $pengajuan);

        if ($pengajuan->status !== 'final_checked_akademik') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan dengan status '.$pengajuan->status.' tidak dapat direject Kaprodi.',
            ], 409);
        }

        $pengajuan->update([
            'status' => 'checking_akademik',
            'catatan_revisi_internal' => $request->string('catatan_revisi_internal'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan dikembalikan ke Staf Akademik untuk direvisi',
            'data' => $pengajuan->fresh(['user', 'yudisiumEvent']),
        ]);
    }

    private function authorizeProdi(Request $request, PengajuanYudisium $pengajuan): void
    {
        $prodiDipimpinId = $request->user()->prodiDipimpin?->id;

        abort_if(
            ! $prodiDipimpinId || $pengajuan->user->program_studi_id !== $prodiDipimpinId,
            403,
            'Anda tidak berwenang memproses pengajuan mahasiswa dari prodi lain.'
        );
    }
}
