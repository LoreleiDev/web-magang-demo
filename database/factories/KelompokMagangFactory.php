<?php

namespace Database\Factories;

use App\Models\KelompokMagang;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KelompokMagang>
 */
class KelompokMagangFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_kelompok' => 'Kelompok '.fake()->unique()->word(),
            'perusahaan_id' => Perusahaan::factory(),
            'guru_pembimbing_id' => User::factory()->guru(),
            'periode_mulai' => now()->subDays(10)->toDateString(),
            'periode_selesai' => now()->addMonths(3)->toDateString(),
        ];
    }
}
