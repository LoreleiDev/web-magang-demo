<?php

namespace Database\Factories;

use App\Models\Perusahaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Perusahaan>
 */
class PerusahaanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'PT '.fake()->company(),
            'alamat' => fake()->address(),
            'bidang_usaha' => 'Teknologi Informasi',
            'daftar_unit_kerja' => ['Jaringan', 'Helpdesk'],
        ];
    }
}
