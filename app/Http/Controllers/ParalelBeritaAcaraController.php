<?php

namespace App\Http\Controllers;

use App\Exceptions\ApprovalException;
use App\Http\Requests\RejectPengajuanRequest;
use App\Models\BeritaAcara;
use App\Services\BeritaAcaraApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dasar untuk Manit & Kadep: alurnya identik dan paralel, beda hanya di kolom approval-nya.
 */
abstract class ParalelBeritaAcaraController extends Controller
{
    /** 'manit' atau 'kadep', diisi oleh subclass. */
    protected string $role;

    public function __construct(protected BeritaAcaraApprovalService $approval) {}

    /**
     * Default: berita acara yang sudah disetujui Kaprodi dan belum diproses role ini.
     * Dengan ?status=approved_kaprodi|completed untuk melihat yang lain.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BeritaAcara::with(['programStudi', 'yudisiumEvent'])
            ->withCount('pengajuan')
            ->whereIn('status', [BeritaAcaraApprovalService::APPROVED_KAPRODI, BeritaAcaraApprovalService::COMPLETED]);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        } else {
            $query->where('status', BeritaAcaraApprovalService::APPROVED_KAPRODI)
                ->whereNull("approved_{$this->role}_at");
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->paginate($request->integer('limit', 15)),
        ]);
    }

    /**
     * Detail + daftar mahasiswa. Untuk preview tabel HTML, pakai GET /berita-acara/{id}/preview.
     */
    public function show(BeritaAcara $beritaAcara): JsonResponse
    {
        if (! in_array($beritaAcara->status, [BeritaAcaraApprovalService::APPROVED_KAPRODI, BeritaAcaraApprovalService::COMPLETED], true)) {
            throw new ApprovalException('Berita acara belum disetujui Kaprodi.', 409);
        }

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
        $beritaAcara = $this->approval->approveParalel($beritaAcara, $request->user(), $this->role);

        $label = ucfirst($this->role);
        $lain = ucfirst($this->role === 'manit' ? 'kadep' : 'manit');

        return response()->json([
            'status' => 'success',
            'message' => $beritaAcara->status === BeritaAcaraApprovalService::COMPLETED
                ? "Disetujui {$label}, berita acara final"
                : "Disetujui {$label}, menunggu approval {$lain}",
            'data' => $beritaAcara,
        ]);
    }

    public function reject(RejectPengajuanRequest $request, BeritaAcara $beritaAcara): JsonResponse
    {
        $beritaAcara = $this->approval->rejectParalel(
            $beritaAcara,
            $request->validated('catatan_revisi_internal'),
            $this->role,
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Berita acara dikembalikan ke Staf Akademik untuk direvisi',
            'data' => $beritaAcara,
        ]);
    }
}
