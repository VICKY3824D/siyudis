<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use App\Models\YudisiumEvent;
use App\Services\BeritaAcaraBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BeritaAcaraPdfController extends Controller
{
    public function __construct(private BeritaAcaraBuilder $builder) {}

    /**
     * Preview berita acara sebagai HTML.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'program_studi_id' => 'required|exists:program_studi,id',
            'yudisium_event_id' => 'required|exists:yudisium_events,id',
        ]);

        $programStudi = ProgramStudi::findOrFail($request->program_studi_id);
        $yudisiumEvent = YudisiumEvent::findOrFail($request->yudisium_event_id);

        // Authorization: cek role
        if (! $this->canAccess($request->user(), $programStudi)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk melihat berita acara program studi ini',
            ], 403);
        }

        $html = $this->builder->build($programStudi, $yudisiumEvent);
        $jumlahMahasiswa = $this->builder->getMahasiswaCount($programStudi, $yudisiumEvent);

        return response()->json([
            'status' => 'success',
            'data' => [
                'html' => $html,
                'jumlah_mahasiswa' => $jumlahMahasiswa,
            ],
        ]);
    }

    /**
     * Export berita acara sebagai PDF.
     */
    public function export(Request $request): Response|JsonResponse
    {
        $request->validate([
            'program_studi_id' => 'required|exists:program_studi,id',
            'yudisium_event_id' => 'required|exists:yudisium_events,id',
        ]);

        $programStudi = ProgramStudi::findOrFail($request->program_studi_id);
        $yudisiumEvent = YudisiumEvent::findOrFail($request->yudisium_event_id);

        // Authorization: cek role
        if (! $this->canAccess($request->user(), $programStudi)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk mengexport berita acara program studi ini',
            ], 403);
        }

        $jumlahMahasiswa = $this->builder->getMahasiswaCount($programStudi, $yudisiumEvent);

        // Validasi: daftar tidak boleh kosong
        if ($jumlahMahasiswa === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada mahasiswa yang memenuhi syarat untuk program studi ini',
            ], 409);
        }

        // Validasi: semua mahasiswa harus sudah approved Manit dan Kadep
        $allApproved = $this->checkAllApproved($programStudi, $yudisiumEvent);
        if (! $allApproved) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum semua mahasiswa disetujui oleh Manager IT dan Kepala Departemen',
            ], 409);
        }

        $html = $this->builder->build($programStudi, $yudisiumEvent);

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'berita-acara-'.str_replace(['/', '\\'], '-', $programStudi->nama_prodi.'-'.$yudisiumEvent->periode).'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Cek apakah user bisa akses berita acara untuk program studi ini.
     */
    private function canAccess($user, ProgramStudi $programStudi): bool
    {
        // Super admin, staf akademik, manit, kadep bisa akses semua
        if (in_array($user->role?->name, ['super_admin', 'staf_akademik', 'manit', 'kadep'])) {
            return true;
        }

        // Kaprodi hanya bisa akses prodi sendiri
        if ($user->role?->name === 'kaprodi' && $user->program_studi_id === $programStudi->id) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah semua mahasiswa sudah di-approve oleh Manit dan Kadep.
     */
    private function checkAllApproved(ProgramStudi $programStudi, YudisiumEvent $yudisiumEvent): bool
    {
        $mahasiswaList = $yudisiumEvent->pengajuan()
            ->whereHas('user', function ($query) use ($programStudi) {
                $query->where('program_studi_id', $programStudi->id);
            })
            ->whereIn('status', ['final_checked_akademik', 'approved_kaprodi', 'completed'])
            ->get();

        return $mahasiswaList->every(function ($pengajuan) {
            return $pengajuan->approved_manit_at !== null && $pengajuan->approved_kadep_at !== null;
        });
    }
}
