<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Program keahlian dikelola superadmin (keputusan 13 no. 15).
     * Tabel lain menyimpan `kode` program ini.
     */
    public function up(): void
    {
        Schema::create('program_keahlian', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_keahlian');
    }
};
