<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResubmitDocumentRequest;
use App\Models\PengajuanFieldValue;
use App\Models\PengajuanYudisium;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengajuanResubmissionController extends Controller
{
    public function store(ResubmitDocumentRequest $request, PengajuanYudisium $pengajuan): JsonResponse
    {
        if ($pengajuan->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses ke pengajuan ini',
            ], 403);
        }

        if ($pengajuan->status !== 'student_revision_requested') {
            return response()->json([
                'status' => 'error',
                'message' => 'Resubmission hanya bisa dilakukan saat status pengajuan meminta revisi',
            ], 409);
        }

        $fieldValue = PengajuanFieldValue::where('pengajuan_id', $pengajuan->id)
            ->where('form_field_id', $request->form_field_id)
            ->first();

        if (! $fieldValue) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field tidak ditemukan pada pengajuan ini',
            ], 404);
        }

        $fieldValue->resubmitValue($request->value);

        return response()->json([
            'status' => 'success',
            'message' => 'Dokumen berhasil disubmit ulang',
            'data' => $fieldValue->fresh(),
        ]);
    }
}