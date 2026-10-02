<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Knowledge base di Gemini File Search (CLAUDE.md bagian 9.3).
     * Satu store per cakupan ("sekolah:TKJ", "industri:5") agar dokumen perusahaan A
     * tidak pernah terambil untuk siswa perusahaan B.
     */
    public function up(): void
    {
        Schema::create('knowledge_base_store', function (Blueprint $table) {
            $table->id();
            $table->string('cakupan', 60)->unique();
            $table->string('nama_store');
            $table->timestamps();
        });

        Schema::table('dokumen_knowledge_base', function (Blueprint $table) {
            $table->string('status_ai', 20)->default('menunggu')->after('id_di_layanan_ai');
            $table->string('pesan_error_ai')->nullable()->after('status_ai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dokumen_knowledge_base', function (Blueprint $table) {
            $table->dropColumn(['status_ai', 'pesan_error_ai']);
        });

        Schema::dropIfExists('knowledge_base_store');
    }
};
