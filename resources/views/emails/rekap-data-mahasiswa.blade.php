<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Data Yudisium</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .data-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .data-table td:first-child {
            font-weight: bold;
            width: 40%;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Konfirmasi Data Yudisium</h2>
    </div>

    <div class="content">
        <p>Yth. {{ $mahasiswa->nama }},</p>

        <p>Data yudisium Anda untuk periode <strong>{{ $event->nama_periode }}</strong> telah diverifikasi oleh staf akademik. Berikut adalah ringkasan data penilaian Anda:</p>

        <table class="data-table">
            <tr>
                <td>IPK</td>
                <td>{{ number_format($data->ipk, 2) }}</td>
            </tr>
            <tr>
                <td>Persentase Nilai D</td>
                <td>{{ number_format($data->persen_nilai_d, 2) }}%</td>
            </tr>
            <tr>
                <td>SKS Ditempuh</td>
                <td>{{ $data->sks_ditempuh }}</td>
            </tr>
            <tr>
                <td>Similarity Index</td>
                <td>{{ number_format($data->similarity_index, 2) }}%</td>
            </tr>
            <tr>
                <td>Skor Bahasa Inggris</td>
                <td>{{ $data->skor_bahasa_inggris }}</td>
            </tr>
            @if($data->sertifikat_level)
            <tr>
                <td>Sertifikat Level</td>
                <td>{{ $data->sertifikat_level }}</td>
            </tr>
            @endif
            <tr>
                <td>Status Judul PA</td>
                <td>{{ ucfirst(str_replace('_', ' ', $data->status_judul_pa)) }}</td>
            </tr>
            @if($data->sertifikat_kompetensi)
            <tr>
                <td>Sertifikat Kompetensi</td>
                <td>{{ $data->sertifikat_kompetensi }}</td>
            </tr>
            @endif
            <tr>
                <td>Status Bebas Pelanggaran</td>
                <td>{{ $data->status_bebas_pelanggaran ? 'Ya' : 'Tidak' }}</td>
            </tr>
        </table>

        <p><strong>Silakan periksa data di atas dan konfirmasi kebenarannya melalui sistem.</strong></p>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/pengajuan/konfirmasi" class="button">Konfirmasi Data</a>
        </div>

        <p style="margin-top: 20px; font-size: 14px; color: #666;">
            Jika data di atas tidak sesuai, Anda dapat menandai sebagai "Data Salah" dan memberikan catatan untuk dilakukan koreksi.
        </p>
    </div>

    <div class="footer">
        <p>Email ini dikirim secara otomatis oleh Sistem Informasi Yudisium.</p>
        <p>Mohon tidak membalas email ini.</p>
    </div>
</body>
</html>
