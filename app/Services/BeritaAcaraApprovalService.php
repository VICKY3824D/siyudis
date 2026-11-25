<?php

namespace App\Services;

use App\Exceptions\ApprovalException;
use App\Models\BeritaAcara;
use App\Models\BeritaAcaraApproval;
use App\Models\PengajuanYudisium;
use App\Models\TandaTangan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            $this->recordApproval($ba, $user, 'kaprodi');
            $ba->status = self::APPROVED_KAPRODI;
            $ba->save();
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

            if ($ba->hasApproved($role)) {
                throw new ApprovalException("Berita acara ini sudah diproses {$label} sebelumnya.", 409);
            }

            $this->recordApproval($ba, $user, $role);

            if ($ba->hasApproved('manit') && $ba->hasApproved('kadep')) {
                $ba->status = self::COMPLETED;
                $this->setStatusPengajuan($ba, 'completed');
            }

            $ba->save();
        });
    }

    public function rejectKaprodi(BeritaAcara $beritaAcara, User $user, string $catatan): BeritaAcara
    {
        return $this->transition($beritaAcara, function (BeritaAcara $ba) use ($user, $catatan) {
            $this->ensureStatus($ba, self::DIAJUKAN, 'direject Kaprodi');
            $this->resetKeStafAkademik($ba, $user, 'kaprodi', $catatan);
        });
    }

    /**
     * @param  'manit'|'kadep'  $role
     */
    public function rejectParalel(BeritaAcara $beritaAcara, User $user, string $catatan, string $role): BeritaAcara
    {
        return $this->transition($beritaAcara, function (BeritaAcara $ba) use ($user, $catatan, $role) {
            $this->ensureStatus($ba, self::APPROVED_KAPRODI, 'direject '.ucfirst($role));
            $this->resetKeStafAkademik($ba, $user, $role, $catatan);
        });
    }

    /**
     * Reject = catat keputusan penolakan pada cycle aktif, majukan current_cycle,
     * dan lepas mahasiswa agar dapat diajukan kembali pada berita acara baru.
     */
    private function resetKeStafAkademik(BeritaAcara $ba, User $user, string $role, string $catatan): void
    {
        $cycle = $ba->current_cycle ?? 1;

        BeritaAcaraApproval::updateOrCreate(
            [
                'berita_acara_id' => $ba->id,
                'cycle' => $cycle,
                'role' => $role,
            ],
            [
                'user_id' => $user->id,
                'approver_nama' => $user->nama,
                'status' => BeritaAcaraApproval::STATUS_REJECTED,
                'signature_path' => null,
                'catatan' => $catatan,
                'acted_at' => now(),
            ],
        );

        $ba->unsetRelation('approvals');

        $this->setStatusPengajuan($ba, 'checking_akademik');
        $ba->pengajuan()->detach();

        $ba->update([
            'status' => self::DITOLAK,
            'current_cycle' => $cycle + 1,
            'catatan_revisi_internal' => $catatan,
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

    /**
     * Catat approval beserta snapshot nama dan file tanda tangan pada tabel berita_acara_approvals.
     */
    private function recordApproval(BeritaAcara $ba, User $user, string $role): void
    {
        $tandaTangan = $this->ensureSignature($user);
        $cycle = $ba->current_cycle ?? 1;

        BeritaAcaraApproval::create([
            'berita_acara_id' => $ba->id,
            'cycle' => $cycle,
            'role' => $role,
            'user_id' => $user->id,
            'approver_nama' => $user->nama,
            'status' => BeritaAcaraApproval::STATUS_APPROVED,
            'signature_path' => $this->snapshotSignature($ba, $tandaTangan, $role, $cycle),
            'catatan' => null,
            'acted_at' => now(),
        ]);

        $ba->unsetRelation('approvals');
    }

    private function ensureSignature(User $user): TandaTangan
    {
        $tandaTangan = $user->tandaTangan;

        if (! $tandaTangan) {
            throw new ApprovalException('Anda belum mengupload e-signature. Upload dulu lewat POST /signature.', 422);
        }

        return $tandaTangan;
    }

    /**
     * Salin file tanda tangan ke path snapshot terpisah agar tidak terpengaruh
     * ketika pejabat mengganti atau menghapus e-signature di kemudian hari.
     */
    private function snapshotSignature(BeritaAcara $ba, TandaTangan $tandaTangan, string $role, int $cycle): ?string
    {
        $sourcePath = $tandaTangan->getRawOriginal('file_path');

        if (! $sourcePath || ! Storage::disk('public')->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $filename = "ba-{$ba->id}-c{$cycle}-{$role}-".Str::uuid().($extension !== '' ? ".{$extension}" : '');
        $snapshotPath = "tanda-tangan-snapshots/{$filename}";

        Storage::disk('public')->copy($sourcePath, $snapshotPath);

        return $snapshotPath;
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

            return $locked->fresh(['programStudi', 'yudisiumEvent', 'pengajuan.user', 'approvals.user']);
        });
    }
}
