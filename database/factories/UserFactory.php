<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\KelompokMagang;
use App\Models\Perusahaan;
use App\Models\ProfilGuru;
use App\Models\ProfilIndustri;
use App\Models\ProfilSiswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Siswa,
            'status_aktif' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => ['status_aktif' => false]);
    }

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Superadmin]);
    }

    public function guru(?string $programKeahlian = 'TKJ'): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Guru])
            ->afterCreating(fn (User $user) => ProfilGuru::create([
                'user_id' => $user->id,
                'nip' => fake()->numerify('19##############'),
                'program_keahlian' => $programKeahlian,
            ]));
    }

    public function industri(?Perusahaan $perusahaan = null): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Industri])
            ->afterCreating(fn (User $user) => ProfilIndustri::create([
                'user_id' => $user->id,
                'perusahaan_id' => ($perusahaan ?? Perusahaan::factory()->create())->id,
                'jabatan' => 'Pembimbing Lapangan',
            ]));
    }

    public function siswa(?KelompokMagang $kelompok = null, string $programKeahlian = 'TKJ', ?string $unitKerja = null): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Siswa])
            ->afterCreating(fn (User $user) => ProfilSiswa::create([
                'user_id' => $user->id,
                'id_siswa' => fake()->unique()->numerify('2324####'),
                'program_keahlian' => $programKeahlian,
                'unit_kerja' => $unitKerja,
                'kelompok_id' => $kelompok?->id,
            ]));
    }
}
