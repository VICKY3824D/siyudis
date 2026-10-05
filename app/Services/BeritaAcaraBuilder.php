<?php

namespace App\Services;

use App\Models\ProgramStudi;
use App\Models\YudisiumEvent;
use Illuminate\Support\Facades\Storage;

class BeritaAcaraBuilder
{
    /**
     * Build HTML berita acara dari program studi dan yudisium event.
     */
    public function build(ProgramStudi $programStudi, YudisiumEvent $yudisiumEvent): string
    {
        // Ambil template (default jika null)
        $template = $programStudi->berita_acara_template
            ?? file_get_contents(resource_path('views/berita-acara/default.blade.php'));

        // Ambil daftar mahasiswa yang sudah final_checked_akademik untuk prodi ini
        $mahasiswaList = $yudisiumEvent->pengajuan()
            ->whereHas('user', function ($query) use ($programStudi) {
                $query->where('program_studi_id', $programStudi->id);
            })
            ->whereIn('status', ['final_checked_akademik', 'approved_kaprodi', 'completed'])
            ->with(['user', 'dataBeritaAcara', 'approvedKaprodiBy', 'approvedManitBy', 'approvedKadepBy'])
            ->get();

        // Build tabel mahasiswa
        $tabelMahasiswa = $this->buildTabelMahasiswa($mahasiswaList);

        // Cek apakah semua mahasiswa sudah di-approve oleh setiap peran
        $semuaApprovedKaprodi = $mahasiswaList->every(fn ($p) => $p->approved_kaprodi_at !== null);
        $semuaApprovedManit = $mahasiswaList->every(fn ($p) => $p->approved_manit_at !== null);
        $semuaApprovedKadep = $mahasiswaList->every(fn ($p) => $p->approved_kadep_at !== null);

        // Ambil data penandatangan
        $kaprodi = $programStudi->kaprodi;
        $manitUser = $mahasiswaList->first()?->approvedManitBy;
        $kadepUser = $mahasiswaList->first()?->approvedKadepBy;

        // Build tanda tangan (hanya tampil jika semua approved)
        $ttdKaprodi = $semuaApprovedKaprodi && $kaprodi?->tandaTangan
            ? $this->buildSignatureImage($kaprodi->tandaTangan->getRawOriginal('file_path'))
            : '';
        $ttdManit = $semuaApprovedManit && $manitUser?->tandaTangan
            ? $this->buildSignatureImage($manitUser->tandaTangan->getRawOriginal('file_path'))
            : '';
        $ttdKadep = $semuaApprovedKadep && $kadepUser?->tandaTangan
            ? $this->buildSignatureImage($kadepUser->tandaTangan->getRawOriginal('file_path'))
            : '';

        // Replacements dengan str_replace untuk keamanan
        $replacements = [
            '{{prodi}}' => $this->escape($programStudi->nama_prodi),
            '{{periode}}' => $this->escape($yudisiumEvent->periode ?? '-'),
            '{{nomor_surat}}' => $this->escape($yudisiumEvent->nomor_surat ?? '-'),
            '{{tanggal_surat}}' => $this->escape($yudisiumEvent->tanggal_surat ?? '-'),
            '{{tabel_mahasiswa}}' => $tabelMahasiswa,
            '{{ttd_kaprodi}}' => $ttdKaprodi,
            '{{ttd_manit}}' => $ttdManit,
            '{{ttd_kadep}}' => $ttdKadep,
            '{{nama_kaprodi}}' => $this->escape($kaprodi?->nama ?? '-'),
            '{{nama_manit}}' => $this->escape($manitUser?->nama ?? '-'),
            '{{nama_kadep}}' => $this->escape($kadepUser?->nama ?? '-'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Build tabel HTML mahasiswa.
     */
    private function buildTabelMahasiswa($mahasiswaList): string
    {
        if ($mahasiswaList->isEmpty()) {
            return '<p>Tidak ada mahasiswa yang memenuhi syarat.</p>';
        }

        $rows = '';
        foreach ($mahasiswaList as $index => $pengajuan) {
            $data = $pengajuan->dataBeritaAcara;
            $rows .= '<tr>';
            $rows .= '<td style="text-align: center;">'.$this->escape($index + 1).'</td>';
            $rows .= '<td>'.$this->escape($pengajuan->user->nomor_induk ?? '-').'</td>';
            $rows .= '<td>'.$this->escape($pengajuan->user->nama ?? '-').'</td>';
            $rows .= '<td style="text-align: center;">'.$this->escape($data?->ipk ?? '-').'</td>';
            $rows .= '<td style="text-align: center;">'.$this->escape($data?->persen_nilai_d ?? '-').'</td>';
            $rows .= '<td style="text-align: center;">'.$this->escape($data?->sks_ditempuh ?? '-').'</td>';
            $rows .= '<td style="text-align: center;">'.$this->escape($data?->similarity_index ?? '-').'</td>';
            $rows .= '<td style="text-align: center;">'.$this->escape($data?->skor_bahasa_inggris ?? '-').'</td>';
            $rows .= '<td style="text-align: center;">'.$this->escape($data?->sertifikat_level ?? '-').'</td>';
            $rows .= '<td>'.$this->escape($data?->status_judul_pa ?? '-').'</td>';
            $rows .= '<td>'.$this->escape($data?->sertifikat_kompetensi ?? '-').'</td>';
            $rows .= '<td style="text-align: center;">'.($data?->status_bebas_pelanggaran ? 'Ya' : 'Tidak').'</td>';
            $rows .= '</tr>';
        }

        return '<table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>IPK</th>
                    <th>Nilai D (%)</th>
                    <th>SKS</th>
                    <th>Similarity</th>
                    <th>Skor Bhs Inggris</th>
                    <th>Sertifikat</th>
                    <th>Judul PA</th>
                    <th>Kompetensi</th>
                    <th>Bebas Pelanggaran</th>
                </tr>
            </thead>
            <tbody>
                '.$rows.'
            </tbody>
        </table>';
    }

    /**
     * Build signature image sebagai base64 data URI.
     */
    private function buildSignatureImage(string $filePath): string
    {
        if (! Storage::disk('public')->exists($filePath)) {
            return '';
        }

        $imageData = Storage::disk('public')->get($filePath);
        $base64 = base64_encode($imageData);
        $mimeType = Storage::disk('public')->mimeType($filePath);

        return '<img src="data:'.$this->escape($mimeType).';base64,'.$base64.'" alt="Tanda Tangan" />';
    }

    /**
     * Escape HTML untuk keamanan.
     */
    private function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get jumlah mahasiswa yang memenuhi syarat.
     */
    public function getMahasiswaCount(ProgramStudi $programStudi, YudisiumEvent $yudisiumEvent): int
    {
        return $yudisiumEvent->pengajuan()
            ->whereHas('user', function ($query) use ($programStudi) {
                $query->where('program_studi_id', $programStudi->id);
            })
            ->whereIn('status', ['final_checked_akademik', 'approved_kaprodi', 'completed'])
            ->count();
    }
}
