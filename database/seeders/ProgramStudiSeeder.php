<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramStudiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('program_studi')->insert([
            [
                'nama_prodi' => 'Teknologi Rekayasa Perangkat Lunak (TRPL)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama_prodi' => 'Teknologi Rekayasa Internet (TRI)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama_prodi' => 'Teknologi Rekayasa Instrumentasi dan Kontrol (TRIK)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama_prodi' => 'Teknologi Rekayasa Elektro (TRE)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
