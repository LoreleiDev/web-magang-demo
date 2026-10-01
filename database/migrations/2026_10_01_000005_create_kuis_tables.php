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
        Schema::create('kuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->unique()->constrained('materi')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('soal_kuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuis_id')->constrained('kuis')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->text('pertanyaan');
            $table->json('pilihan');
            $table->unsignedTinyInteger('jawaban_benar');
            $table->text('pembahasan')->nullable();
            $table->timestamps();
        });

        // Semua percobaan disimpan; skor terbaik ditampilkan (keputusan 13 no. 22).
        Schema::create('hasil_assessment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kuis_id')->constrained('kuis')->cascadeOnDelete();
            $table->unsignedTinyInteger('skor');
            $table->boolean('lulus');
            $table->timestamp('tanggal');
            $table->timestamps();

            $table->index(['siswa_id', 'kuis_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_assessment');
        Schema::dropIfExists('soal_kuis');
        Schema::dropIfExists('kuis');
    }
};
