<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBeritaAcaraRequest;
use App\Models\YudisiumEvent;
use Illuminate\Http\JsonResponse;

class BeritaAcaraController extends Controller
{
    /**
     * Update nomor surat, tanggal surat, periode berita acara.
     */
    public function update(UpdateBeritaAcaraRequest $request, YudisiumEvent $event): JsonResponse
    {
        $event->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Data berita acara berhasil diperbarui',
            'data' => $event->fresh(['form', 'programStudi']),
        ]);
    }
}
