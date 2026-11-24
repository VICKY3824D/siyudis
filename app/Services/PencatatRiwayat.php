<?php

namespace App\Services;

use App\Models\BeritaAcara;
use App\Models\PengajuanYudisium;
use App\Models\RiwayatPerubahan;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PencatatRiwayat
{
    public function catat(
        User $pelaku,
        string $modul,
        string $jenis,
        PengajuanYudisium|BeritaAcara|null $objek,
        string $ringkasan,
        ?string $catatan = null,
        array $perubahanField = []
    ): void {
        try {
            $riwayat = RiwayatPerubahan::create([
                'user_id' => $pelaku->id,
                'role_name' => $pelaku->role?->name ?? 'unknown',
                'modul' => $modul,
                'jenis' => $jenis,
                'pengajuan_id' => $objek instanceof PengajuanYudisium ? $objek->id : null,
                'berita_acara_id' => $objek instanceof BeritaAcara ? $objek->id : null,
                'ringkasan' => $ringkasan,
                'catatan' => $catatan,
            ]);

            if (!empty($perubahanField)) {
                foreach ($perubahanField as $field => $nilai) {
                    $riwayat->detail()->create([
                        'field' => $field,
                        'nilai_lama' => $nilai['lama'] ?? null,
                        'nilai_baru' => $nilai['baru'] ?? null,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Gagal mencatat riwayat perubahan', [
                'user_id' => $pelaku->id,
                'modul' => $modul,
                'jenis' => $jenis,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
