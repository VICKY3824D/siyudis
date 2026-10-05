<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTandaTanganRequest;
use App\Http\Requests\UpdateTandaTanganRequest;
use App\Models\TandaTangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TandaTanganController extends Controller
{
    public function store(StoreTandaTanganRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->tandaTangan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah punya e-signature. Gunakan PUT /signature/me untuk mengganti.',
            ], 409);
        }

        $path = $request->file('file')->store('tanda-tangan', 'public');

        $tandaTangan = TandaTangan::create([
            'user_id' => $user->id,
            'file_path' => $path,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => ['file_path' => $tandaTangan->file_path],
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        $tandaTangan = $request->user()->tandaTangan;

        if (! $tandaTangan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum upload e-signature.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => ['file_path' => $tandaTangan->file_path],
        ]);
    }

    public function update(UpdateTandaTanganRequest $request): JsonResponse
    {
        $user = $request->user();
        $existing = $user->tandaTangan;

        $path = $request->file('file')->store('tanda-tangan', 'public');

        $tandaTangan = TandaTangan::updateOrCreate(
            ['user_id' => $user->id],
            ['file_path' => $path]
        );

        if ($existing && $existing->getRawOriginal('file_path') !== $path) {
            Storage::disk('public')->delete($existing->getRawOriginal('file_path'));
        }

        return response()->json([
            'status' => 'success',
            'data' => ['file_path' => $tandaTangan->file_path],
        ]);
    }
}
