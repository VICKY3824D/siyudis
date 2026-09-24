<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'nomor_induk',
        'nama',
        'email',
        'google_id',
        'role_id',
        'program_studi_id',
        'avatar',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* ==================== RELASI ELOQUENT ==================== */

    /**
     * Role user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Program studi tempat user bernaung (Mahasiswa / Dosen / Kaprodi).
     */
    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id');
    }

    /**
     * Program studi yang dipimpin (khusus jika user ber-role 'kaprodi').
     */
    public function prodiDipimpin(): HasOne
    {
        return $this->hasOne(ProgramStudi::class, 'kaprodi_id');
    }

    /**
     * File tanda tangan elektronik (e-sign) milik user.
     */
    public function tandaTangan(): HasOne
    {
        return $this->hasOne(TandaTangan::class, 'user_id');
    }

    /**
     * Pengajuan yudisium milik user (khusus Mahasiswa, hanya boleh 1x submit).
     */
    public function pengajuanYudisium(): HasOne
    {
        return $this->hasOne(PengajuanYudisium::class, 'user_id');
    }

    /* ==================== HELPER METHODS ==================== */

    public function isMahasiswa(): bool
    {
        return $this->role === 'mahasiswa';
    }

    public function isStafAkademik(): bool
    {
        return $this->role === 'staf_akademik';
    }

    public function isKaprodi(): bool
    {
        return $this->role === 'kaprodi';
    }

    public function isManit(): bool
    {
        return $this->role === 'manit';
    }

    public function isKadep(): bool
    {
        return $this->role === 'kadep';
    }
}
