<?php

namespace Database\Factories;

use App\Models\BeritaAcara;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeritaAcara>
 */
class BeritaAcaraFactory extends Factory
{
    public function definition(): array
    {
        return [
            'yudisium_event_id' => \App\Models\YudisiumEvent::factory(),
            'program_studi_id' => \App\Models\ProgramStudi::factory(),
            'nomor_surat' => null,
            'tanggal_surat' => null,
            'periode' => fake()->word(),
            'status' => 'diajukan',
            'diajukan_by' => \App\Models\User::factory(),
            'diajukan_at' => now(),
            'approved_kaprodi_by' => null,
            'approved_kaprodi_at' => null,
            'approved_manit_by' => null,
            'approved_manit_at' => null,
            'approved_kadep_by' => null,
            'approved_kadep_at' => null,
            'catatan_revisi_internal' => null,
        ];
    }
}
