<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Seeder production: hanya membuat akun superadmin pertama. Guru, siswa, industri,
 * perusahaan, dan program keahlian dibuat superadmin lewat panel.
 *
 * Email & password diambil dari .env (ADMIN_EMAIL, ADMIN_PASSWORD) agar password
 * tidak tersimpan di repository. Data contoh untuk lokal ada di DemoSeeder.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = config('magang.admin');

        if (blank($admin['password'] ?? null)) {
            throw new RuntimeException('Isi ADMIN_PASSWORD di file .env sebelum menjalankan seeder (lalu jalankan php artisan config:clear).');
        }

        // Aman dijalankan berulang: akun yang sudah ada diperbarui, bukan diduplikasi.
        User::updateOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['nama'],
                'password' => $admin['password'],
                'role' => Role::Superadmin,
                'status_aktif' => true,
            ],
        );

        $this->command->info("Akun superadmin siap: {$admin['email']}");
    }
}
