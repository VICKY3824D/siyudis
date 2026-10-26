<?php

namespace App\Http\Controllers\Kaprodi;

use App\Exceptions\ApprovalException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectPengajuanRequest;
use App\Models\BeritaAcara;
use App\Services\BeritaAcaraApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KaprodiBeritaAcaraController extends Controller
{
    public function __construct(private BeritaAcaraApprovalService $approval) {}

    /**
     * Berita acara prodi sendiri. Default: yang menunggu approval (status diajukan),
     * bisa difilter lewat ?status=.
     */
    public function index(Request $request): JsonResponse
    {
        $beritaAcara = BeritaAcara::with(['programStudi', 'yudisiumEvent'])
            ->withCount('pengajuan')
            ->where('program_studi_id', $request->user()->program_studi_id)
            ->where('status', $request->query('status', BeritaAcaraApprovalService::DIAJUKAN))
            ->latest()
            ->paginate($request->integer('limit', 15));

        return response()->json([
            'status' => 'success',
            'data' => $beritaAcara,
        ]);
    }

    public function show(Request $request, BeritaAcara $beritaAcara): JsonResponse
    {
        $this->authorizeProdi($request, $beritaAcara);

        $beritaAcara->load([
            'programStudi', 'yudisiumEvent', 'pengajuan.user', 'pengajuan.dataBeritaAcara',
            'approvedKaprodiBy', 'approvedManitBy', 'approvedKadepBy',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $beritaAcara,
        ]);
    }

    public function approve(Request $request, BeritaAcara $beritaAcara): JsonResponse
    {
        $this->authorizeProdi($request, $beritaAcara);

        $beritaAcara = $this->approval->approveKaprodi($beritaAcara, $request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Berita acara disetujui, diteruskan paralel ke Manit & Kadep',
            'data' => $beritaAcara,
        ]);
    }

    public function reject(RejectPengajuanRequest $request, BeritaAcara $beritaAcara): JsonResponse
    {
        $this->authorizeProdi($request, $beritaAcara);

        $beritaAcara = $this->approval->rejectKaprodi(
            $beritaAcara,
            $request->validated('catatan_revisi_internal'),
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Berita acara dikembalikan ke Staf Akademik untuk direvisi',
            'data' => $beritaAcara,
        ]);
    }

    /**
     * Kaprodi hanya boleh memproses berita acara prodinya sendiri
     * (dicocokkan lewat users.program_studi_id, sama seperti BeritaAcaraPdfController).
     */
    private function authorizeProdi(Request $request, BeritaAcara $beritaAcara): void
    {
        if ((int) $beritaAcara->program_studi_id !== (int) $request->user()->program_studi_id) {
            throw new ApprovalException('Anda tidak berwenang memproses berita acara program studi lain.', 403);
        }
    }
}
