<?php

namespace App\Http\Controllers;

use App\Http\Requests\KonfirmasiMahasiswaRequest;
use App\Http\Resources\DataPenilaianResource;
use App\Models\PengajuanYudisium;
use App\Services\PencatatRiwayat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengajuanKonfirmasiController extends Controller
{
    public function __construct(private PencatatRiwayat $pencatat) {}

    /**
     * GET data penilaian milik mahasiswa yang login.
     */
    public function rekap(Request $request): JsonResponse
    {
        $pengajuan = PengajuanYudisium::with('dataBeritaAcara')
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$pengajuan || !$pengajuan->dataBeritaAcara) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data penilaian belum tersedia.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new DataPenilaianResource($pengajuan->dataBeritaAcara),
        ]);
    }

    /**
     * Konfirmasi data oleh mahasiswa (benar/salah).
     */
    public function konfirmasi(KonfirmasiMahasiswaRequest $request): JsonResponse
    {
        $pengajuan = PengajuanYudisium::where('user_id', $request->user()->id)->first();

        if (!$pengajuan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan tidak ditemukan.',
            ], 404);
        }

        // Validasi status
        if ($pengajuan->status !== 'waiting_student_confirmation') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pengajuan dengan status waiting_student_confirmation yang dapat dikonfirmasi.',
            ], 409);
        }

        $pengajuan->load(['user.programStudi']);
        $namaMahasiswa = $pengajuan->user->nama ?? 'Unknown';
        $namaProdi = $pengajuan->user->programStudi->kode ?? 'Unknown';

        if ($request->konfirmasi === 'benar') {
            $pengajuan->update(['status' => 'confirmed_by_student']);

            $this->pencatat->catat(
                pelaku: $request->user(),
                modul: 'pengajuan_yudisium',
                jenis: 'perubahan_data',
                objek: $pengajuan,
                ringkasan: "Mahasiswa {$namaMahasiswa} ({$namaProdi}) mengkonfirmasi data benar"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil dikonfirmasi sebagai benar.',
                'data' => $pengajuan->fresh(['user', 'yudisiumEvent']),
            ]);
        }

        // konfirmasi === 'salah'
        $pengajuan->update([
            'status' => 'student_revision_requested',
            'catatan_koreksi_mahasiswa' => $request->catatan,
        ]);

        $this->pencatat->catat(
            pelaku: $request->user(),
            modul: 'pengajuan_yudisium',
            jenis: 'pengajuan_revisi',
            objek: $pengajuan,
            ringkasan: "Mahasiswa {$namaMahasiswa} ({$namaProdi}) melaporkan data salah",
            catatan: $request->catatan
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Data ditandai salah, staf akademik akan melakukan koreksi.',
            'data' => $pengajuan->fresh(['user', 'yudisiumEvent']),
        ]);
    }
}
