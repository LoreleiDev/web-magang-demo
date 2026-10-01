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
        Schema::create('perusahaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('bidang_usaha')->nullable();
            $table->json('daftar_unit_kerja');
            $table->timestamps();
        });

        // Satu kelompok hanya punya satu guru pembimbing; satu guru boleh banyak kelompok.
        Schema::create('kelompok_magang', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelompok');
            $table->foreignId('perusahaan_id')->constrained('perusahaan')->restrictOnDelete();
            $table->foreignId('guru_pembimbing_id')->constrained('users')->restrictOnDelete();
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelompok_magang');
        Schema::dropIfExists('perusahaan');
    }
};
