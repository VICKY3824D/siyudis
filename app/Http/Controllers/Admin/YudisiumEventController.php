<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreYudisiumEventRequest;
use App\Http\Requests\UpdateYudisiumEventRequest;
use App\Models\YudisiumEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class YudisiumEventController extends Controller
{
    /**
     * Menampilkan daftar periode yudisium.
     */
    public function index(Request $request): JsonResponse
    {
        $query = YudisiumEvent::with(['form:id,nama_form', 'programStudi:id,nama_prodi'])
            ->withCount('pengajuan');

        if ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->program_studi_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $events = $query->latest('id')->get();

        return response()->json([
            'status' => 'success',
            'data' => $events,
        ]);
    }

    /**
     * Membuat periode yudisium baru.
     */
    public function store(StoreYudisiumEventRequest $request): JsonResponse
    {
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : true;

        if ($isActive) {
            $hasOverlap = YudisiumEvent::where('program_studi_id', $request->input('program_studi_id'))
                ->where('is_active', true)
                ->where(function ($q) use ($request) {
                    $q->where('tgl_buka', '<=', $request->input('tgl_tutup'))
                        ->where('tgl_tutup', '>=', $request->input('tgl_buka'));
                })
                ->exists();

            if ($hasOverlap) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event aktif tumpang tindih untuk program studi ini',
                ], 409);
            }
        }

        $event = YudisiumEvent::create($request->validated());
        $event->load(['form:id,nama_form', 'programStudi:id,nama_prodi']);

        return response()->json([
            'status' => 'success',
            'message' => 'Periode yudisium berhasil dibuat',
            'data' => $event,
        ], 201);
    }

    /**
     * Menampilkan detail periode yudisium beserta form dan field-nya.
     */
    public function show(YudisiumEvent $event): JsonResponse
    {
        $event->load([
            'form.fields' => function ($query) {
                $query->orderBy('order_position', 'asc');
            },
            'programStudi:id,nama_prodi',
        ])->loadCount('pengajuan');

        return response()->json([
            'status' => 'success',
            'data' => $event,
        ]);
    }

    /**
     * Memperbarui data periode yudisium.
     */
    public function update(UpdateYudisiumEventRequest $request, YudisiumEvent $event): JsonResponse
    {
        $targetIsActive = $request->has('is_active') ? $request->boolean('is_active') : $event->is_active;
        $targetProdiId = $request->input('program_studi_id', $event->program_studi_id);
        $targetBuka = $request->input('tgl_buka', $event->tgl_buka);
        $targetTutup = $request->input('tgl_tutup', $event->tgl_tutup);

        if ($targetIsActive) {
            $hasOverlap = YudisiumEvent::where('program_studi_id', $targetProdiId)
                ->where('id', '!=', $event->id)
                ->where('is_active', true)
                ->where(function ($q) use ($targetBuka, $targetTutup) {
                    $q->where('tgl_buka', '<=', $targetTutup)
                        ->where('tgl_tutup', '>=', $targetBuka);
                })
                ->exists();

            if ($hasOverlap) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event aktif tumpang tindih untuk program studi ini',
                ], 409);
            }
        }

        $event->update($request->validated());
        $event->load(['form:id,nama_form', 'programStudi:id,nama_prodi']);

        return response()->json([
            'status' => 'success',
            'message' => 'Periode yudisium berhasil diperbarui',
            'data' => $event,
        ]);
    }

    /**
     * Menghapus periode yudisium jika belum memiliki data pengajuan mahasiswa.
     */
    public function destroy(YudisiumEvent $event): JsonResponse
    {
        if ($event->pengajuan()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus periode yudisium karena sudah terdapat pengajuan mahasiswa yang terdaftar.',
            ], 422);
        }

        $event->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Periode yudisium berhasil dihapus',
        ]);
    }
}
