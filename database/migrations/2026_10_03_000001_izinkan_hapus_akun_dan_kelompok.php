<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Superadmin boleh menghapus akun & kelompok (keputusan 13 no. 25–26, revisi):
 * - Guru dihapus: kelompok, kompetensi, materi, dan dokumennya tetap ada (kolom pemilik jadi null).
 * - Industri dihapus: riwayat verifikasi tetap ada, nama pemeriksa disimpan sebagai teks.
 */
return new class extends Migration
{
    /** @var array<string, string> tabel => kolom yang menunjuk users */
    private array $pemilik = [
        'kelompok_magang' => 'guru_pembimbing_id',
        'kompetensi' => 'dibuat_oleh',
        'materi' => 'dibuat_oleh',
        'dokumen_knowledge_base' => 'diunggah_oleh',
        'verifikasi_materi' => 'diperiksa_oleh',
    ];

    public function up(): void
    {
        foreach ($this->pemilik as $tabel => $kolom) {
            Schema::table($tabel, function (Blueprint $table) use ($kolom) {
                $table->dropForeign([$kolom]);
            });
            Schema::table($tabel, function (Blueprint $table) use ($kolom) {
                $table->unsignedBigInteger($kolom)->nullable()->change();
                $table->foreign($kolom)->references('id')->on('users')->nullOnDelete();
            });
        }

        Schema::table('verifikasi_materi', function (Blueprint $table) {
            $table->string('nama_pemeriksa')->nullable()->after('diperiksa_oleh');
        });
        Schema::table('progres_kompetensi', function (Blueprint $table) {
            $table->string('nama_verifikator')->nullable()->after('diverifikasi_oleh');
        });

        // Isi nama untuk riwayat yang sudah ada.
        foreach (DB::table('verifikasi_materi')->whereNotNull('diperiksa_oleh')->get(['id', 'diperiksa_oleh']) as $v) {
            DB::table('verifikasi_materi')->where('id', $v->id)
                ->update(['nama_pemeriksa' => DB::table('users')->where('id', $v->diperiksa_oleh)->value('name')]);
        }
        foreach (DB::table('progres_kompetensi')->whereNotNull('diverifikasi_oleh')->get(['id', 'diverifikasi_oleh']) as $p) {
            DB::table('progres_kompetensi')->where('id', $p->id)
                ->update(['nama_verifikator' => DB::table('users')->where('id', $p->diverifikasi_oleh)->value('name')]);
        }
    }

    public function down(): void
    {
        Schema::table('progres_kompetensi', fn (Blueprint $table) => $table->dropColumn('nama_verifikator'));
        Schema::table('verifikasi_materi', fn (Blueprint $table) => $table->dropColumn('nama_pemeriksa'));

        foreach ($this->pemilik as $tabel => $kolom) {
            Schema::table($tabel, function (Blueprint $table) use ($kolom) {
                $table->dropForeign([$kolom]);
            });
            Schema::table($tabel, function (Blueprint $table) use ($kolom) {
                $table->unsignedBigInteger($kolom)->nullable(false)->change();
                $table->foreign($kolom)->references('id')->on('users')->restrictOnDelete();
            });
        }
    }
};
