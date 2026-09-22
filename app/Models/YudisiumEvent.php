<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YudisiumEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'program_studi_id',
        'nama_event',
        'periode',
        'tgl_buka',
        'tgl_tutup',
        'tgl_yudisium',
        'is_active',
    ];

    protected $casts = [
        'tgl_buka' => 'datetime',
        'tgl_tutup' => 'datetime',
        'tgl_yudisium' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Accessor untuk nama periode lengkap (gabungan nama_event dan periode).
     */
    public function getNamaPeriodeAttribute(): string
    {
        return trim(($this->nama_event ?? '').' '.($this->periode ?? ''));
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(PengajuanYudisium::class, 'yudisium_event_id');
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id');
    }
}
