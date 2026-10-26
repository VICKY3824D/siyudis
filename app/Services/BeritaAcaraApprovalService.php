<?php

namespace App\Services;

use App\Exceptions\ApprovalException;
use App\Models\BeritaAcara;
use App\Models\PengajuanYudisium;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BeritaAcaraApprovalService
{
    public const DIAJUKAN = 'diajukan';

    public const APPROVED_KAPRODI = 'approved_kaprodi';

    public const COMPLETED = 'completed';

    public const DITOLAK = 'ditolak';

    public function approveKaprodi(BeritaAcara $beritaAcara, User $user): BeritaAcara
    {
        return $this->transition($beritaAcara, function (BeritaAcara $ba) use ($user) {
            $this->ensureStatus($ba, self::DIAJUKAN, 'di-approve Kaprodi');
            $this->ensureSignature($user);

            $ba->update([
                'status' => self::APPROVED_KAPRODI,
                'approved_kaprodi_by' => $user->id,
                'approved_kaprodi_at' => now(),
            ]);
        });
    }

    /**
     * Approve Manit/Kadep (paralel, urutan bebas). Status jadi "completed"
     * hanya kalau keduanya sudah approve.
     *
     * @param  'manit'|'kadep'  $role
     */
    public function approveParalel(BeritaAcara $beritaAcara, User $user, string $role): BeritaAcara
    {
        return $this->transition($beritaAcara, function (BeritaAcara $ba) use ($user, $role) {
            $label = ucfirst($role);

            $this->ensureStatus($ba, self::APPROVED_KAPRODI, "di-approve {$label}");

            if ($ba->{"approved_{$role}_at"} !== null) {
                throw new ApprovalException("Berita acara ini sudah diproses {$label} sebelumnya.", 409);
            }

            $this->ensureSignature($user);

            $ba->{"approved_{$role}_by"} = $user->id;
            $ba->{"approved_{$role}_at"} = now();

            if ($ba->approved_manit_at !== null && $ba->approved_kadep_at !== null) {
                $ba->status = self::COMPLETED;
                $this->setStatusPengajuan($ba, 'completed');
            }

            $ba->save();
        });
    }

    public function rejectKaprodi(BeritaAcara $beritaAcara, string $catatan): BeritaAcara
    {
        return $this->transition($beritaAcara, function (BeritaAcara $ba) use ($catatan) {
            $this->ensureStatus($ba, self::DIAJUKAN, 'direject Kaprodi');
            $this->resetKeStafAkademik($ba, $catatan);
        });
    }

    /**
     * @param  'manit'|'kadep'  $role
     */
    public function rejectParalel(BeritaAcara $beritaAcara, string $catatan, string $role): BeritaAcara
    {
        return $this->transition($beritaAcara, function (BeritaAcara $ba) use ($catatan, $role) {
            $this->ensureStatus($ba, self::APPROVED_KAPRODI, 'direject '.ucfirst($role));
            $this->resetKeStafAkademik($ba, $catatan);
        });
    }

    /**
     * Reject = seluruh berita acara kembali ke staf akademik. Semua approval
     * di-reset (Kaprodi akan approve ulang nanti) dan mahasiswa dilepas dari
     * berita acara supaya bisa dimasukkan ke berita acara baru.
     */
    private function resetKeStafAkademik(BeritaAcara $ba, string $catatan): void
    {
        $this->setStatusPengajuan($ba, 'checking_akademik');
        $ba->pengajuan()->detach();

        $ba->update([
            'status' => self::DITOLAK,
            'catatan_revisi_internal' => $catatan,
            'approved_kaprodi_by' => null,
            'approved_kaprodi_at' => null,
            'approved_manit_by' => null,
            'approved_manit_at' => null,
            'approved_kadep_by' => null,
            'approved_kadep_at' => null,
        ]);
    }

    private function setStatusPengajuan(BeritaAcara $ba, string $status): void
    {
        PengajuanYudisium::whereIn('id', $ba->pengajuan()->pluck('pengajuan_yudisium.id'))
            ->update(['status' => $status]);
    }

    private function ensureStatus(BeritaAcara $ba, string $expected, string $aksi): void
    {
        if ($ba->status !== $expected) {
            throw new ApprovalException("Berita acara dengan status {$ba->status} tidak dapat {$aksi}.", 409);
        }
    }

    private function ensureSignature(User $user): void
    {
        if (! $user->tandaTangan) {
            throw new ApprovalException('Anda belum mengupload e-signature. Upload dulu lewat POST /signature.', 422);
        }
    }

    /**
     * Jalankan perubahan status dalam transaksi + row lock, supaya approve
     * Manit dan Kadep yang masuk bersamaan tidak saling menimpa.
     */
    private function transition(BeritaAcara $ba, callable $callback): BeritaAcara
    {
        return DB::transaction(function () use ($ba, $callback) {
            $locked = BeritaAcara::query()->lockForUpdate()->findOrFail($ba->getKey());

            $callback($locked);

            return $locked->fresh(['programStudi', 'yudisiumEvent', 'pengajuan.user']);
        });
    }
}
