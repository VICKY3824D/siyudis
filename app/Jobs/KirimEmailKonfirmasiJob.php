<?php

namespace App\Jobs;

use App\Mail\RekapDataMahasiswaMail;
use App\Models\PengajuanYudisium;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class KirimEmailKonfirmasiJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public PengajuanYudisium $pengajuan;

    /**
     * Create a new job instance.
     */
    public function __construct(PengajuanYudisium $pengajuan)
    {
        $this->pengajuan = $pengajuan;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load relations
        $this->pengajuan->load(['user', 'yudisiumEvent', 'dataBeritaAcara']);

        // Kirim email
        Mail::to($this->pengajuan->user->email)->send(new RekapDataMahasiswaMail($this->pengajuan));

        // Update jika berhasil
        $this->pengajuan->update([
            'email_status' => 'sent',
            'email_sent_at' => now(),
            'email_error' => null,
            'status' => 'waiting_student_confirmation',
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        // Update jika gagal, status pengajuan TIDAK berubah
        $errorMessage = $exception->getMessage();

        // Batasi 500 karakter
        if (strlen($errorMessage) > 500) {
            $errorMessage = substr($errorMessage, 0, 500);
        }

        $this->pengajuan->update([
            'email_status' => 'failed',
            'email_error' => $errorMessage,
        ]);
    }
}
