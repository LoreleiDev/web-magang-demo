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
        Schema::create('kompetensi', function (Blueprint $table) {
            $table->id();
            $table->string('program_keahlian', 20)->index();
            $table->string('nama_kompetensi_sekolah');
            $table->text('aktivitas_kompetensi_industri');
            $table->unsignedTinyInteger('target_level');
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('progres_kompetensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kompetensi_id')->constrained('kompetensi')->cascadeOnDelete();
            $table->unsignedTinyInteger('level_siswa')->nullable();
            $table->foreignId('level_diisi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_level_diisi')->nullable();
            $table->string('status', 30)->default('belum_dipelajari');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'kompetensi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progres_kompetensi');
        Schema::dropIfExists('kompetensi');
    }
};
