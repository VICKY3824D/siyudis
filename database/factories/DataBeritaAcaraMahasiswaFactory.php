<?php

namespace Database\Factories;

use App\Models\DataBeritaAcaraMahasiswa;
use App\Models\PengajuanYudisium;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataBeritaAcaraMahasiswa>
 */
class DataBeritaAcaraMahasiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pengajuan_id' => PengajuanYudisium::factory(),
            'ipk' => $this->faker->randomFloat(2, 2.75, 4.00),
            'persen_nilai_d' => $this->faker->randomFloat(2, 0, 15),
            'sks_ditempuh' => $this->faker->numberBetween(140, 150),
            'similarity_index' => $this->faker->randomFloat(2, 0, 25),
            'skor_bahasa_inggris' => $this->faker->numberBetween(400, 600),
            'sertifikat_level' => $this->faker->randomElement(['A1', 'A2', 'B1', 'B2', 'C1', 'C2']),
            'status_judul_pa' => $this->faker->sentence(),
            'sertifikat_kompetensi' => $this->faker->sentence(),
            'status_bebas_pelanggaran' => $this->faker->boolean(80),
        ];
    }
}
