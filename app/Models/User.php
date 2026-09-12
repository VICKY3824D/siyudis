<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nomor_induk',
        'nama',
        'email',
        'google_id',
        'role',
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

//    /**
//     * Daftar pengajuan yudisium yang dibuat (khusus Mahasiswa).
//     */
//    public function pengajuanYudisium(): HasMany
//    {
//        return $this->hasMany(PengajuanYudisium::class, 'user_id');
//    }

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
