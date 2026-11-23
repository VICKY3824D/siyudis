<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiwayatPerubahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiwayatPerubahanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RiwayatPerubahan::with([
            'user:id,nama,email,role_id',
            'user.role:id,name',
            'pengajuan.user:id,nama,nomor_induk,program_studi_id',
            'pengajuan.user.programStudi:id,nama,kode',
            'beritaAcara.programStudi:id,nama,kode',
        ])->latest();

        if ($request->filled('role_name')) {
            $query->where('role_name', $request->role_name);
        }

        if ($request->filled('modul')) {
            $query->where('modul', $request->modul);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_sampai);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->whereHas('user', function ($query) use ($q) {
                    $query->where('nama', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->orWhereHas('pengajuan.user', function ($query) use ($q) {
                    $query->where('nama', 'like', "%{$q}%")
                        ->orWhere('nomor_induk', 'like', "%{$q}%");
                });
            });
        }

        $riwayat = $query->paginate($request->integer('limit', 15));

        return response()->json([
            'status' => 'success',
            'data' => $riwayat,
        ]);
    }

    public function ringkasan(Request $request): JsonResponse
    {
        $query = RiwayatPerubahan::query();

        if ($request->filled('role_name')) {
            $query->where('role_name', $request->role_name);
        }

        if ($request->filled('modul')) {
            $query->where('modul', $request->modul);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_sampai);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->whereHas('user', function ($query) use ($q) {
                    $query->where('nama', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->orWhereHas('pengajuan.user', function ($query) use ($q) {
                    $query->where('nama', 'like', "%{$q}%")
                        ->orWhere('nomor_induk', 'like', "%{$q}%");
                });
            });
        }

        $ringkasan = [
            'pengajuan_revisi' => (clone $query)->where('jenis', 'pengajuan_revisi')->count(),
            'dikembalikan' => (clone $query)->where('jenis', 'dikembalikan')->count(),
            'perubahan_data' => (clone $query)->where('jenis', 'perubahan_data')->count(),
            'pengesahan' => (clone $query)->where('jenis', 'pengesahan')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $ringkasan,
        ]);
    }

    public function show(RiwayatPerubahan $riwayat): JsonResponse
    {
        $riwayat->load([
            'user:id,nama,email,role_id',
            'user.role:id,name',
            'pengajuan.user:id,nama,nomor_induk,program_studi_id',
            'pengajuan.user.programStudi:id,nama,kode',
            'beritaAcara.programStudi:id,nama,kode',
            'detail',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $riwayat,
        ]);
    }
}
