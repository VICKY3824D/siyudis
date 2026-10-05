<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\YudisiumEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<YudisiumEvent>
 */
class YudisiumEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tglBuka = $this->faker->dateTimeBetween('now', '+1 week');
        $tglTutup = $this->faker->dateTimeBetween($tglBuka, '+2 weeks');

        return [
            'form_id' => Form::factory(),
            'nama_event' => $this->faker->sentence(3),
            'periode' => $this->faker->year().'/'.($this->faker->year() + 1),
            'tgl_buka' => $tglBuka,
            'tgl_tutup' => $tglTutup,
            'tgl_yudisium' => $this->faker->dateTimeBetween($tglTutup, '+1 month'),
            'is_active' => true,
        ];
    }
}
