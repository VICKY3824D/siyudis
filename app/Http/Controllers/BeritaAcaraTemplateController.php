<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BeritaAcaraTemplateController extends Controller
{
    /**
     * Placeholder yang diperbolehkan dalam template.
     */
    private const ALLOWED_PLACEHOLDERS = [
        '{{prodi}}',
        '{{periode}}',
        '{{nomor_surat}}',
        '{{tanggal_surat}}',
        '{{tabel_mahasiswa}}',
        '{{ttd_kaprodi}}',
        '{{ttd_manit}}',
        '{{ttd_kadep}}',
        '{{nama_kaprodi}}',
        '{{nama_manit}}',
        '{{nama_kadep}}',
    ];

    /**
     * GET template berita acara untuk program studi.
     */
    public function show(ProgramStudi $prodi): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'template' => $prodi->berita_acara_template,
                'allowed_placeholders' => self::ALLOWED_PLACEHOLDERS,
            ],
        ]);
    }

    /**
     * PUT (update) template berita acara untuk program studi.
     */
    public function update(Request $request, ProgramStudi $prodi): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        $template = $request->input('template');

        // Validasi: tolak tag <script>
        if ($template && stripos($template, '<script') !== false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template tidak boleh mengandung tag <script>',
            ], 422);
        }

        // Validasi: cek placeholder yang tidak dikenal (opsional warning)
        if ($template) {
            preg_match_all('/\{\{[^}]+\}\}/', $template, $matches);
            $usedPlaceholders = $matches[0] ?? [];
            $unknownPlaceholders = array_diff($usedPlaceholders, self::ALLOWED_PLACEHOLDERS);

            if (! empty($unknownPlaceholders)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Template mengandung placeholder yang tidak dikenal: '.implode(', ', $unknownPlaceholders),
                    'allowed_placeholders' => self::ALLOWED_PLACEHOLDERS,
                ], 422);
            }
        }

        $prodi->update(['berita_acara_template' => $template]);

        return response()->json([
            'status' => 'success',
            'message' => 'Template berita acara berhasil diperbarui',
            'data' => [
                'template' => $prodi->fresh()->berita_acara_template,
            ],
        ]);
    }
}
