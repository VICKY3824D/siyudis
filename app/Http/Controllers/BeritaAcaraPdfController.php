<?php

namespace App\Http\Controllers;

use App\Models\BeritaAcara;
use App\Services\BeritaAcaraBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BeritaAcaraPdfController extends Controller
{
    public function __construct(private BeritaAcaraBuilder $builder) {}

    public function preview(Request $request, BeritaAcara $beritaAcara): JsonResponse
    {
        if (! $this->canAccess($request->user(), $beritaAcara)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk melihat berita acara ini',
            ], 403);
        }

        $html = $this->builder->build($beritaAcara);
        $jumlahMahasiswa = $beritaAcara->pengajuan()->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'html' => $html,
                'jumlah_mahasiswa' => $jumlahMahasiswa,
            ],
        ]);
    }

    public function export(Request $request, BeritaAcara $beritaAcara): Response|JsonResponse
    {
        if (! $this->canAccess($request->user(), $beritaAcara)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk mengexport berita acara ini',
            ], 403);
        }

        $jumlahMahasiswa = $beritaAcara->pengajuan()->count();

        if ($jumlahMahasiswa === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada mahasiswa dalam berita acara ini',
            ], 409);
        }

        if ($beritaAcara->approved_manit_at === null || $beritaAcara->approved_kadep_at === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum semua mahasiswa disetujui oleh Manager IT dan Kepala Departemen',
            ], 409);
        }

        $html = $this->builder->build($beritaAcara);

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'berita-acara-'.str_replace(['/', '\\'], '-', $beritaAcara->programStudi->nama_prodi.'-'.$beritaAcara->periode).'.pdf';

        return $pdf->stream($filename);
    }

    private function canAccess($user, BeritaAcara $beritaAcara): bool
    {
        if (in_array($user->role?->name, ['super_admin', 'staf_akademik', 'manit', 'kadep'])) {
            return true;
        }

        if ($user->role?->name === 'kaprodi' && $user->program_studi_id === $beritaAcara->program_studi_id) {
            return true;
        }

        if ($user->role?->name === 'mahasiswa') {
            return $beritaAcara->pengajuan()
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }
}
