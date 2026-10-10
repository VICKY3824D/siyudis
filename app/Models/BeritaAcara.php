<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'diajukan_by',
        'diajukan_at',
        'approved_kaprodi_by',
        'approved_kaprodi_at',
        'approved_manit_by',
        'approved_manit_at',
        'approved_kadep_by',
        'approved_kadep_at',
        'catatan_revisi_internal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'diajukan_at' => 'datetime',
            'approved_kaprodi_at' => 'datetime',
            'approved_manit_at' => 'datetime',
            'approved_kadep_at' => 'datetime',
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

    public function approvedKaprodiBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_kaprodi_by');
    }

    public function approvedManitBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_manit_by');
    }

    public function approvedKadepBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_kadep_by');
    }
}
