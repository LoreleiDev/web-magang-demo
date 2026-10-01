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
        // Satu logbook per tanggal per siswa (keputusan 13 no. 21).
        Schema::create('logbook', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('aktivitas');
            $table->text('peralatan_software')->nullable();
            $table->text('sudah_dipahami')->nullable();
            $table->text('baru_ditemui')->nullable();
            $table->text('kesulitan')->nullable();
            $table->text('pengetahuan_sekolah_digunakan')->nullable();
            $table->text('ingin_dipelajari')->nullable();
            $table->string('bukti_kegiatan')->nullable();
            $table->json('hasil_analisis_ai')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'tanggal']);
        });

        Schema::create('dokumen_knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);
            $table->string('program_keahlian', 20)->nullable()->index();
            $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaan')->cascadeOnDelete();
            $table->string('nama_file');
            $table->string('path_file');
            $table->foreignId('diunggah_oleh')->constrained('users')->restrictOnDelete();
            $table->string('id_di_layanan_ai')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_ai_mentor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->json('pesan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_ai_mentor');
        Schema::dropIfExists('dokumen_knowledge_base');
        Schema::dropIfExists('logbook');
    }
};
