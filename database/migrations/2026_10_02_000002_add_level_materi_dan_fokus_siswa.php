<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Level siswa naik otomatis dari kuis materi berlevel (bagian 11.1) dan
     * kompetensi fokus dipilih siswa saat Mulai Pendampingan (bagian 4.1).
     */
    public function up(): void
    {
        Schema::table('materi', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->default(1)->after('judul');
            $table->unsignedTinyInteger('nilai_minimal')->default(75)->after('level');
        });

        Schema::table('profil_siswa', function (Blueprint $table) {
            $table->foreignId('kompetensi_fokus_id')->nullable()->after('kelompok_id')
                ->constrained('kompetensi')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_siswa', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kompetensi_fokus_id');
        });

        Schema::table('materi', function (Blueprint $table) {
            $table->dropColumn(['level', 'nilai_minimal']);
        });
    }
};
