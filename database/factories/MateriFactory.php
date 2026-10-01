<?php

namespace Database\Factories;

use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materi>
 */
class MateriFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kompetensi_id' => Kompetensi::factory(),
            'judul' => fake()->sentence(4),
            'dibuat_oleh' => User::factory()->guru(),
            'diubah_terakhir' => now(),
            'terverifikasi_industri' => false,
        ];
    }
}
