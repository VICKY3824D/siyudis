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
    private const OVERLAP_MESSAGE = 'Event aktif tumpang tindih dengan periode yudisium lain';

    /**
     * Menampilkan daftar periode yudisium.
     */
    public function index(Request $request): JsonResponse
    {
        $query = YudisiumEvent::with('form:id,nama_form')
            ->withCount('pengajuan');

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
            $hasOverlap = YudisiumEvent::where('is_active', true)
                ->where('tgl_buka', '<=', $request->input('tgl_tutup'))
                ->where('tgl_tutup', '>=', $request->input('tgl_buka'))
                ->exists();

            if ($hasOverlap) {
                return response()->json([
                    'status' => 'error',
                    'message' => self::OVERLAP_MESSAGE,
                ], 409);
            }
        }

        $event = YudisiumEvent::create($request->validated());
        $event->load('form:id,nama_form');

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
        $targetBuka = $request->input('tgl_buka', $event->tgl_buka);
        $targetTutup = $request->input('tgl_tutup', $event->tgl_tutup);

        if ($targetIsActive) {
            $hasOverlap = YudisiumEvent::where('id', '!=', $event->id)
                ->where('is_active', true)
                ->where('tgl_buka', '<=', $targetTutup)
                ->where('tgl_tutup', '>=', $targetBuka)
                ->exists();

            if ($hasOverlap) {
                return response()->json([
                    'status' => 'error',
                    'message' => self::OVERLAP_MESSAGE,
                ], 409);
            }
        }

        $event->update($request->validated());
        $event->load('form:id,nama_form');

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
