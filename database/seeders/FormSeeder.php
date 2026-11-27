<?php

namespace Database\Seeders;

use App\Models\Form;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Form::query()->firstOrCreate(
            ['nama_form' => 'Form Pengajuan Yudisium'],
            ['deskripsi' => 'Form identitas mahasiswa dan dokumen persyaratan yudisium.'],
        );
    }
}
