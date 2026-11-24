<?php

namespace Database\Factories;

use App\Models\RiwayatPerubahan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatPerubahan>
 */
class RiwayatPerubahanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'role_name' => fake()->randomElement(['staf_akademik', 'mahasiswa', 'kaprodi', 'manit', 'kadep']),
            'modul' => fake()->randomElement(['pengajuan_yudisium', 'berita_acara']),
            'jenis' => fake()->randomElement(['pengajuan_revisi', 'dikembalikan', 'perubahan_data', 'pengesahan']),
            'pengajuan_id' => null,
            'berita_acara_id' => null,
            'ringkasan' => fake()->sentence(),
            'catatan' => fake()->optional()->paragraph(),
        ];
    }
}
