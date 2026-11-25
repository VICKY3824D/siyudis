<?php

namespace Database\Factories;

use App\Models\BeritaAcara;
use App\Models\BeritaAcaraApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeritaAcaraApproval>
 */
class BeritaAcaraApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'berita_acara_id' => BeritaAcara::factory(),
            'cycle' => 1,
            'role' => 'kaprodi',
            'user_id' => User::factory(),
            'approver_nama' => fake()->name(),
            'status' => BeritaAcaraApproval::STATUS_APPROVED,
            'signature_path' => null,
            'catatan' => null,
            'acted_at' => now(),
        ];
    }
}
