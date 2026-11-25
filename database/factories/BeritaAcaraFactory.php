<?php

namespace Database\Factories;

use App\Models\BeritaAcara;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeritaAcara>
 */
class BeritaAcaraFactory extends Factory
{
    public function definition(): array
    {
        return [
            'yudisium_event_id' => YudisiumEvent::factory(),
            'program_studi_id' => ProgramStudi::factory(),
            'nomor_surat' => null,
            'tanggal_surat' => null,
            'periode' => fake()->word(),
            'status' => 'diajukan',
            'current_cycle' => 1,
            'diajukan_by' => User::factory(),
            'diajukan_at' => now(),
            'catatan_revisi_internal' => null,
        ];
    }
}
