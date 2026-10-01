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
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kompetensi_id')->constrained('kompetensi')->restrictOnDelete();
            $table->string('judul');
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamp('diubah_terakhir')->nullable();
            $table->boolean('terverifikasi_industri')->default(false);
            $table->timestamps();
        });

        // 8 langkah microlearning (CLAUDE.md bagian 6.4).
        Schema::create('langkah_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->constrained('materi')->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan');
            $table->string('jenis', 30);
            $table->longText('konten_teks')->nullable();
            $table->timestamps();

            $table->unique(['materi_id', 'urutan']);
        });

        // Media hanya berupa link (CLAUDE.md bagian 6.4.1).
        Schema::create('media_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('langkah_materi_id')->constrained('langkah_materi')->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan')->default(1);
            $table->string('jenis', 20);
            $table->string('url', 500);
            $table->string('id_media', 100);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('verifikasi_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->constrained('materi')->cascadeOnDelete();
            $table->foreignId('diperiksa_oleh')->constrained('users')->restrictOnDelete();
            $table->foreignId('perusahaan_id')->constrained('perusahaan')->restrictOnDelete();
            $table->string('hasil', 20);
            $table->text('masukan')->nullable();
            $table->boolean('email_terkirim')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifikasi_materi');
        Schema::dropIfExists('media_materi');
        Schema::dropIfExists('langkah_materi');
        Schema::dropIfExists('materi');
    }
};
