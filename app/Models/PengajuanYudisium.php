<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanYudisium extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_yudisium';

    protected $fillable = [
        'user_id',
        'yudisium_event_id',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
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
}
