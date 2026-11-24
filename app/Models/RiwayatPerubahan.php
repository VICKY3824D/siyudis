<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiwayatPerubahan extends Model
{
    use HasFactory;

    protected $table = 'riwayat_perubahan';

    protected $fillable = [
        'user_id',
        'role_name',
        'modul',
        'jenis',
        'pengajuan_id',
        'berita_acara_id',
        'ringkasan',
        'catatan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanYudisium::class, 'pengajuan_id');
    }

    public function beritaAcara(): BelongsTo
    {
        return $this->belongsTo(BeritaAcara::class, 'berita_acara_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(RiwayatPerubahanDetail::class, 'riwayat_perubahan_id');
    }
}
