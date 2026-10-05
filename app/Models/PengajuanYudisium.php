<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengajuanYudisium extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_yudisium';

    protected $fillable = [
        'user_id',
        'yudisium_event_id',
        'status',
        'submitted_at',
        'checked_akademik_by',
        'catatan_koreksi_mahasiswa',
        'catatan_revisi_internal',
        'email_status',
        'email_sent_at',
        'email_error',
        'email_attempts',
        'approved_kaprodi_by',
        'approved_kaprodi_at',
        'approved_manit_by',
        'approved_manit_at',
        'approved_kadep_by',
        'approved_kadep_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'email_sent_at' => 'datetime',
            'approved_kaprodi_at' => 'datetime',
            'approved_manit_at' => 'datetime',
            'approved_kadep_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function yudisiumEvent(): BelongsTo
    {
        return $this->belongsTo(YudisiumEvent::class, 'yudisium_event_id');
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(PengajuanFieldValue::class, 'pengajuan_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_akademik_by');
    }

    public function dataBeritaAcara(): HasOne
    {
        return $this->hasOne(DataBeritaAcaraMahasiswa::class, 'pengajuan_id');
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