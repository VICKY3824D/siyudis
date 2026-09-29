<?php

namespace App\Mail;

use App\Models\PengajuanYudisium;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RekapDataMahasiswaMail extends Mailable
{
    use Queueable, SerializesModels;

    public PengajuanYudisium $pengajuan;

    /**
     * Create a new message instance.
     */
    public function __construct(PengajuanYudisium $pengajuan)
    {
        $this->pengajuan = $pengajuan;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Konfirmasi Data Yudisium',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.rekap-data-mahasiswa',
            with: [
                'pengajuan' => $this->pengajuan,
                'mahasiswa' => $this->pengajuan->user,
                'event' => $this->pengajuan->yudisiumEvent,
                'data' => $this->pengajuan->dataBeritaAcara,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
