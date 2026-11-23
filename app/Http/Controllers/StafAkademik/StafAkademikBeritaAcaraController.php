<?php

namespace App\Http\Controllers\StafAkademik;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBeritaAcaraRequest;
use App\Http\Requests\UpdateBeritaAcaraRequest;
use App\Models\BeritaAcara;
use App\Models\PengajuanYudisium;
use App\Models\YudisiumEvent;
use App\Services\PencatatRiwayat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StafAkademikBeritaAcaraController extends Controller
{
    public function __construct(private PencatatRiwayat $pencatat) {}

    public function store(StoreBeritaAcaraRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $pengajuanList = PengajuanYudisium::with('user')
            ->whereIn('id', $validated['pengajuan_ids'])
            ->get();

        if ($pengajuanList->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada pengajuan yang valid',
            ], 422);
        }

        foreach ($pengajuanList as $pengajuan) {
            if ($pengajuan->status !== 'final_checked_akademik') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Semua pengajuan harus berstatus final_checked_akademik',
                ], 422);
            }

            if ($pengajuan->user->program_studi_id !== (int) $validated['program_studi_id']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Semua mahasiswa harus dari program studi yang sama',
                ], 422);
            }

            if ($pengajuan->beritaAcara()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Salah satu pengajuan sudah terdaftar di berita acara lain',
                ], 409);
            }
        }

        $event = YudisiumEvent::findOrFail($validated['yudisium_event_id']);

        $beritaAcara = BeritaAcara::create([
            'yudisium_event_id' => $validated['yudisium_event_id'],
            'program_studi_id' => $validated['program_studi_id'],
            'periode' => $event->periode,
            'status' => 'diajukan',
            'diajukan_by' => $request->user()->id,
            'diajukan_at' => now(),
        ]);

        $beritaAcara->pengajuan()->attach($validated['pengajuan_ids']);

        $beritaAcara->load(['programStudi']);
        $namaProdi = $beritaAcara->programStudi->nama ?? 'Unknown';
        $jumlahMahasiswa = count($validated['pengajuan_ids']);

        $this->pencatat->catat(
            pelaku: $request->user(),
            modul: 'berita_acara',
            jenis: 'pengesahan',
            objek: $beritaAcara,
            ringkasan: "Mengajukan berita acara {$namaProdi} dengan {$jumlahMahasiswa} mahasiswa"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Berita acara berhasil dibuat',
            'data' => $beritaAcara->load(['yudisiumEvent', 'programStudi', 'pengajuan']),
        ], 201);
    }

    public function update(UpdateBeritaAcaraRequest $request, BeritaAcara $beritaAcara): JsonResponse
    {
        $beritaAcara->load(['programStudi']);
        $nilaiLama = $beritaAcara->only(['nomor_surat', 'tanggal_surat', 'periode']);
        $nilaiBaru = $request->validated();

        $perubahanField = [];
        $jumlahPerubahan = 0;

        foreach ($nilaiBaru as $field => $baru) {
            $lama = $nilaiLama[$field] ?? null;
            if ($lama != $baru) {
                $perubahanField[$field] = ['lama' => $lama, 'baru' => $baru];
                $jumlahPerubahan++;
            }
        }

        $beritaAcara->update($nilaiBaru);

        if ($jumlahPerubahan > 0) {
            $namaProdi = $beritaAcara->programStudi->nama ?? 'Unknown';

            $this->pencatat->catat(
                pelaku: $request->user(),
                modul: 'berita_acara',
                jenis: 'perubahan_data',
                objek: $beritaAcara,
                ringkasan: "Memperbarui {$jumlahPerubahan} field berita acara {$namaProdi}",
                perubahanField: $perubahanField
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data berita acara berhasil diperbarui',
            'data' => $beritaAcara->fresh(['yudisiumEvent', 'programStudi']),
        ]);
    }
}
