<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    /**
     * Upload satu file dokumen (dipakai untuk field bertipe pdf/image),
     * maksimal 5MB. File URL hasilnya dipakai sebagai `value` saat submit
     * pengajuan (lihat PengajuanController::store).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $path = $request->file('file')->store('pengajuan-dokumen', 'public');

        return response()->json([
            'status' => 'success',
            'data' => [
                'file_url' => asset('storage/'.$path),
                'mime_type' => $request->file('file')->getMimeType(),
                'size' => $request->file('file')->getSize(),
            ],
        ], 201);
    }
}
