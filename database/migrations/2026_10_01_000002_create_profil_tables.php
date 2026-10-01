<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Perusahaan & periode magang siswa diambil dari kelompok, tidak disimpan di sini.
        Schema::create('profil_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('id_siswa', 30)->unique();
            $table->string('program_keahlian', 20)->index();
            $table->string('unit_kerja')->nullable();
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompok_magang')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('profil_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nip', 30)->nullable();
            $table->string('program_keahlian', 20)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('profil_industri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('perusahaan_id')->constrained('perusahaan')->restrictOnDelete();
            $table->string('jabatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_industri');
        Schema::dropIfExists('profil_guru');
        Schema::dropIfExists('profil_siswa');
    }
};
