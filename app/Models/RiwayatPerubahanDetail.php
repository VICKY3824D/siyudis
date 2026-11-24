<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPerubahanDetail extends Model
{
    protected $table = 'riwayat_perubahan_detail';

    protected $fillable = [
        'riwayat_perubahan_id',
        'field',
        'nilai_lama',
        'nilai_baru',
    ];

    public function riwayatPerubahan(): BelongsTo
    {
        return $this->belongsTo(RiwayatPerubahan::class, 'riwayat_perubahan_id');
    }
}
