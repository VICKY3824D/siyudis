<?php

namespace App\Models;

use Database\Factories\BeritaAcaraApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaAcaraApproval extends Model
{
    /** @use HasFactory<BeritaAcaraApprovalFactory> */
    use HasFactory;

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'berita_acara_approvals';

    protected $fillable = [
        'berita_acara_id',
        'cycle',
        'role',
        'user_id',
        'approver_nama',
        'status',
        'signature_path',
        'catatan',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'cycle' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    public function beritaAcara(): BelongsTo
    {
        return $this->belongsTo(BeritaAcara::class, 'berita_acara_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
