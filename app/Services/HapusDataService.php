<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\KelompokMagang;
use App\Models\ProfilSiswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Hapus akun & kelompok oleh superadmin (keputusan 13 no. 25–26, revisi pemilik proyek).
 *
 * - Siswa: semua datanya (logbook + file bukti, hasil kuis, progres, chat AI) ikut terhapus permanen.
 * - Guru: kelompok, kompetensi, materi, dan dokumennya tetap ada; kelompok menjadi tanpa guru
 *   pembimbing sampai superadmin menetapkan guru baru (kolom pemilik di-null-kan oleh database).
 * - Industri: riwayat verifikasi tetap ada; nama pemeriksa sudah disimpan sebagai teks.
 */
class HapusDataService
{
    public function hapusAkun(User $akun): void
    {
        $fileBukti = $akun->hasRole(Role::Siswa)
            ? $akun->logbook()->whereNotNull('bukti_kegiatan')->pluck('bukti_kegiatan')->all()
            : [];

        DB::transaction(function () use ($akun) {
            // Paksa keluar jika sedang login (driver sesi database).
            if (config('session.driver') === 'database') {
                DB::table((string) config('session.table', 'sessions'))->where('user_id', $akun->id)->delete();
            }

            // Profil, logbook, progres, hasil kuis, dan chat terhapus lewat cascade.
            $akun->delete();
        });

        Storage::disk('local')->delete($fileBukti);
    }

    /**
     * Siswa di kelompok ini menjadi tanpa kelompok dan memilih unit kerja lagi (keputusan 27).
     * Data logbook & kuis siswa tetap ada.
     */
    public function hapusKelompok(KelompokMagang $kelompok): int
    {
        return DB::transaction(function () use ($kelompok) {
            $jumlahSiswa = ProfilSiswa::where('kelompok_id', $kelompok->id)
                ->update(['kelompok_id' => null, 'unit_kerja' => null]);

            $kelompok->delete();

            return $jumlahSiswa;
        });
    }
}
