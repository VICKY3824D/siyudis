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
        'nama_periode',
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
     * Relasi ke template Form yang digunakan pada event ini.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Relasi ke pengajuan mahasiswa (akan dibuat pada langkah berikutnya).
     */
    public function pengajuan(): HasMany
    {
        return $this->hasMany(PengajuanYudisium::class);
    }
}
