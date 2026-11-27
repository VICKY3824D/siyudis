<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormField;
use App\Models\ProgramStudi;
use Illuminate\Database\Seeder;

class FormFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (ProgramStudi::query()->doesntExist()) {
            $this->call(ProgramStudiSeeder::class);
        }

        $form = Form::query()->first() ?? Form::query()->create([
            'nama_form' => 'Form Pengajuan Yudisium',
            'deskripsi' => 'Form identitas mahasiswa dan dokumen persyaratan yudisium.',
        ]);

        $prodiList = ProgramStudi::query()->orderBy('id')->get();
        $triProdi = $prodiList->first(
            fn (ProgramStudi $prodi): bool => str_contains($prodi->nama_prodi, '(TRI)')
                || str_contains(strtolower($prodi->nama_prodi), 'internet')
        );
        $otherProdiList = $prodiList->reject(
            fn (ProgramStudi $prodi): bool => $triProdi !== null && $prodi->id === $triProdi->id
        );

        $fields = [
            // Identitas Mahasiswa
            [
                'program_studi_id' => null,
                'field_name' => 'Nama Lengkap',
                'field_desc' => 'Nama lengkap mahasiswa sesuai identitas akademik',
                'data_type' => 'freetext',
                'is_required' => true,
                'order_position' => 1,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'NIM',
                'field_desc' => 'Nomor Induk Mahasiswa lengkap (contoh: 22/501982/SV/21416)',
                'data_type' => 'freetext',
                'is_required' => true,
                'order_position' => 2,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'No. WA',
                'field_desc' => 'Nomor WhatsApp aktif mahasiswa',
                'data_type' => 'freetext',
                'is_required' => true,
                'order_position' => 3,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Tempat/Tanggal Lahir',
                'field_desc' => 'Tempat dan tanggal lahir mahasiswa',
                'data_type' => 'freetext',
                'is_required' => true,
                'order_position' => 4,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Dosen Pembimbing',
                'field_desc' => 'Nama lengkap dan gelar dosen pembimbing tugas akhir',
                'data_type' => 'freetext',
                'is_required' => true,
                'order_position' => 5,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Predikat Kelulusan',
                'field_desc' => 'Predikat kelulusan mahasiswa',
                'data_type' => 'freetext',
                'is_required' => false,
                'order_position' => 6,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Tautan (Link) Video Presentasi Tugas Akhir',
                'field_desc' => 'Tautan URL video presentasi tugas akhir',
                'data_type' => 'link',
                'is_required' => true,
                'order_position' => 7,
            ],

            // Dokumen Persyaratan (01 - 15)
            [
                'program_studi_id' => null,
                'field_name' => 'Form Pengajuan Yudisium',
                'field_desc' => null,
                'data_type' => 'pdf',
                'is_required' => true,
                'order_position' => 8,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Form Pembatalan Mata Kuliah Pilihan',
                'field_desc' => null,
                'data_type' => 'pdf',
                'is_required' => true,
                'order_position' => 9,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Form Checklist Judul Proyek Akhir',
                'field_desc' => null,
                'data_type' => 'pdf',
                'is_required' => true,
                'order_position' => 10,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Pas Photo',
                'field_desc' => 'Pasfoto ukuran 4x6',
                'data_type' => 'image',
                'is_required' => true,
                'order_position' => 11,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Transkrip Nilai Sementara',
                'field_desc' => null,
                'data_type' => 'pdf',
                'is_required' => true,
                'order_position' => 12,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Surat Tanda Terima Menyerahkan Skripsi dan Bebas Perpustakaan UGM',
                'field_desc' => null,
                'data_type' => 'pdf',
                'is_required' => true,
                'order_position' => 13,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Ijazah Terakhir',
                'field_desc' => null,
                'data_type' => 'pdf',
                'is_required' => true,
                'order_position' => 14,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Sertifikat PPSMB',
                'field_desc' => null,
                'data_type' => 'pdf',
                'is_required' => true,
                'order_position' => 15,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Hasil Cek Plagiasi',
                'field_desc' => 'Hasil cek plagiasi (Turnitin)',
                'data_type' => 'pdf',
                'is_required' => true,
                'order_position' => 16,
            ],
            [
                'program_studi_id' => null,
                'field_name' => 'Sertifikat Kemampuan Bahasa Inggris',
                'field_desc' => 'Sertifikat AcEPT / kemampuan bahasa Inggris',
                'data_type' => 'pdf',
                'is_required' => true,
                'order_position' => 17,
            ],
        ];

        // 11. Sertifikat Kompetensi: Wajib TRI, Prodi Lain Opsional
        if ($triProdi !== null) {
            $fields[] = [
                'program_studi_id' => $triProdi->id,
                'field_name' => 'Sertifikat Kompetensi',
                'field_desc' => 'Wajib TRI',
                'data_type' => 'pdf',
                'is_required' => true,
                'order_position' => 18,
            ];

            foreach ($otherProdiList as $prodi) {
                $fields[] = [
                    'program_studi_id' => $prodi->id,
                    'field_name' => 'Sertifikat Kompetensi',
                    'field_desc' => 'Prodi Lain Opsional',
                    'data_type' => 'pdf',
                    'is_required' => false,
                    'order_position' => 18,
                ];
            }
        } else {
            $fields[] = [
                'program_studi_id' => null,
                'field_name' => 'Sertifikat Kompetensi',
                'field_desc' => 'Wajib TRI, Prodi Lain Opsional',
                'data_type' => 'pdf',
                'is_required' => false,
                'order_position' => 18,
            ];
        }

        $fields[] = [
            'program_studi_id' => null,
            'field_name' => 'Lembar Halaman Pengesahan',
            'field_desc' => null,
            'data_type' => 'pdf',
            'is_required' => true,
            'order_position' => 19,
        ];
        $fields[] = [
            'program_studi_id' => null,
            'field_name' => 'Unggah Draft Publikasi/Makalah',
            'field_desc' => null,
            'data_type' => 'pdf',
            'is_required' => true,
            'order_position' => 20,
        ];
        $fields[] = [
            'program_studi_id' => null,
            'field_name' => 'Transkrip Nilai D3',
            'field_desc' => 'Khusus Mahasiswa Alih Program (Tidak berlaku bagi mahasiswa jalur reguler)',
            'data_type' => 'pdf',
            'is_required' => false,
            'order_position' => 21,
        ];
        $fields[] = [
            'program_studi_id' => $triProdi?->id,
            'field_name' => 'Surat Bebas Lab',
            'field_desc' => 'Wajib TRI',
            'data_type' => 'pdf',
            'is_required' => true,
            'order_position' => 22,
        ];

        foreach ($fields as $field) {
            FormField::query()->updateOrCreate(
                [
                    'form_id' => $form->id,
                    'program_studi_id' => $field['program_studi_id'],
                    'field_name' => $field['field_name'],
                ],
                [
                    'field_desc' => $field['field_desc'],
                    'data_type' => $field['data_type'],
                    'options' => null,
                    'is_required' => $field['is_required'],
                    'is_visible' => true,
                    'order_position' => $field['order_position'],
                ],
            );
        }
    }
}
