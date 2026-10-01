<?php

namespace Database\Factories;

use App\Models\Kompetensi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kompetensi>
 */
class KompetensiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_keahlian' => 'TKJ',
            'nama_kompetensi_sekolah' => fake()->sentence(3),
            'aktivitas_kompetensi_industri' => fake()->sentence(8),
            'target_level' => 3,
            'dibuat_oleh' => User::factory()->guru(),
        ];
    }
}
