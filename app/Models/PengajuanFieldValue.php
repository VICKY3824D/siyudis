<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanFieldValue extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_field_values';

    protected $fillable = [
        'pengajuan_id',
        'form_field_id',
        'value',
    ];

//    public function pengajuanYudisium(): BelongsTo
//    {
//        return $this->belongsTo(PengajuanYudisium::class, 'pengajuan_id');
//    }

    public function pengajuanYudisium(): BelongsTo
    {
        return $this->belongsTo(PengajuanYudisium::class, 'pengajuan_id');
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }
}
