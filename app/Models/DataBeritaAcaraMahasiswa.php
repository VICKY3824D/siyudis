<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataBeritaAcaraMahasiswa extends Model
{
    use HasFactory;

    protected $table = 'data_berita_acara_mahasiswa';

    protected $fillable = [
        'pengajuan_id',
        'ipk',
        'persen_nilai_d',
        'sks_ditempuh',
        'similarity_index',
        'skor_bahasa_inggris',
        'sertifikat_level',
        'status_judul_pa',
        'sertifikat_kompetensi',
        'status_bebas_pelanggaran',
    ];

    protected function casts(): array
    {
        return [
            'ipk' => 'decimal:2',
            'persen_nilai_d' => 'decimal:2',
            'similarity_index' => 'decimal:2',
            'status_bebas_pelanggaran' => 'boolean',
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanYudisium::class, 'pengajuan_id');
    }
}
