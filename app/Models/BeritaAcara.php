<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeritaAcara extends Model
{
    use HasFactory;

    protected $table = 'berita_acara';

    protected $fillable = [
        'yudisium_event_id',
        'program_studi_id',
        'nomor_surat',
        'tanggal_surat',
        'periode',
        'status',
        'current_cycle',
        'diajukan_by',
        'diajukan_at',
        'catatan_revisi_internal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'current_cycle' => 'integer',
            'diajukan_at' => 'datetime',
        ];
    }

    public function yudisiumEvent(): BelongsTo
    {
        return $this->belongsTo(YudisiumEvent::class, 'yudisium_event_id');
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id');
    }

    public function pengajuan(): BelongsToMany
    {
        return $this->belongsToMany(PengajuanYudisium::class, 'berita_acara_pengajuan', 'berita_acara_id', 'pengajuan_id')
            ->withTimestamps();
    }

    public function diajukanBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BeritaAcaraApproval::class, 'berita_acara_id')
            ->orderBy('cycle')
            ->orderBy('acted_at');
    }

    public function approvalForRole(string $role, ?int $cycle = null): ?BeritaAcaraApproval
    {
        $targetCycle = $cycle ?? $this->current_cycle ?? 1;

        if ($this->relationLoaded('approvals')) {
            return $this->approvals
                ->first(fn (BeritaAcaraApproval $approval) => (int) $approval->cycle === (int) $targetCycle && $approval->role === $role);
        }

        return $this->approvals()
            ->where('cycle', $targetCycle)
            ->where('role', $role)
            ->first();
    }

    public function hasApproved(string $role, ?int $cycle = null): bool
    {
        return $this->approvalForRole($role, $cycle)?->status === BeritaAcaraApproval::STATUS_APPROVED;
    }

    public function riwayatPerubahan(): HasMany
    {
        return $this->hasMany(RiwayatPerubahan::class, 'berita_acara_id');
    }
}
