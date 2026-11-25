<?php

namespace App\Services;

use App\Models\BeritaAcara;
use App\Models\BeritaAcaraApproval;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class BeritaAcaraBuilder
{
    public function build(BeritaAcara $beritaAcara): string
    {
        $beritaAcara->loadMissing(['programStudi.kaprodi', 'approvals.user.tandaTangan']);

        $programStudi = $beritaAcara->programStudi;

        $template = $programStudi->berita_acara_template
            ?? file_get_contents(resource_path('views/berita-acara/default.blade.php'));

        $mahasiswaList = $beritaAcara->pengajuan()
            ->with(['user', 'dataBeritaAcara'])
            ->get();

        $tabelMahasiswa = $this->buildTabelMahasiswa($mahasiswaList);

        $kaprodiData = $this->resolveApproverData($beritaAcara, 'kaprodi', $programStudi->kaprodi);
        $manitData = $this->resolveApproverData($beritaAcara, 'manit');
        $kadepData = $this->resolveApproverData($beritaAcara, 'kadep');

        $replacements = [
            '{{prodi}}' => $this->escape($programStudi->nama_prodi),
            '{{periode}}' => $this->escape($beritaAcara->periode ?? '-'),
            '{{nomor_surat}}' => $this->escape($beritaAcara->nomor_surat ?? '-'),
            '{{tanggal_surat}}' => $this->escape($beritaAcara->tanggal_surat ?? '-'),
            '{{tabel_mahasiswa}}' => $tabelMahasiswa,
            '{{ttd_kaprodi}}' => $kaprodiData['signature'],
            '{{ttd_manit}}' => $manitData['signature'],
            '{{ttd_kadep}}' => $kadepData['signature'],
            '{{nama_kaprodi}}' => $this->escape($kaprodiData['nama']),
            '{{nama_manit}}' => $this->escape($manitData['nama']),
            '{{nama_kadep}}' => $this->escape($kadepData['nama']),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Resolve signature HTML dan nama approver dari tabel berita_acara_approvals.
     *
     * @return array{signature: string, nama: string}
     */
    private function resolveApproverData(BeritaAcara $beritaAcara, string $role, ?User $unapprovedFallbackUser = null): array
    {
        $approval = $beritaAcara->approvalForRole($role);
        $isApproved = $approval?->status === BeritaAcaraApproval::STATUS_APPROVED;

        if (! $isApproved) {
            return [
                'signature' => '',
                'nama' => $unapprovedFallbackUser?->nama ?? '-',
            ];
        }

        $approverUser = $approval->user;
        $nama = $approval->approver_nama ?? $approverUser?->nama ?? '-';
        $signaturePath = $approval->signature_path ?? $approverUser?->tandaTangan?->getRawOriginal('file_path');

        return [
            'signature' => $signaturePath ? $this->buildSignatureImage($signaturePath) : '',
            'nama' => $nama,
        ];
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
}
