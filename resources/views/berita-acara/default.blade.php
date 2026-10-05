<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Berita Acara Yudisium</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            margin: 20px;
        }
        h1 {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            vertical-align: top;
            padding: 10px;
        }
        .signature-box img {
            max-width: 150px;
            max-height: 80px;
            margin: 10px 0;
        }
        .signature-name {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BERITA ACARA YUDISIUM</h1>
        <p>Program Studi: {{prodi}}</p>
        <p>Periode: {{periode}}</p>
        <p>Nomor: {{nomor_surat}}</p>
        <p>Tanggal: {{tanggal_surat}}</p>
    </div>

    <div class="content">
        <p>Berikut adalah daftar mahasiswa yang telah memenuhi syarat untuk mengikuti yudisium:</p>

        {{tabel_mahasiswa}}
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <p>Ketua Program Studi</p>
            {{ttd_kaprodi}}
            <div class="signature-name">{{nama_kaprodi}}</div>
        </div>
        <div class="signature-box">
            <p>Manager IT</p>
            {{ttd_manit}}
            <div class="signature-name">{{nama_manit}}</div>
        </div>
        <div class="signature-box">
            <p>Kepala Departemen</p>
            {{ttd_kadep}}
            <div class="signature-name">{{nama_kadep}}</div>
        </div>
    </div>
</body>
</html>
