<?php

namespace App\Support;

use App\Models\Logbook;

/**
 * Bentuk data logbook untuk tampilan baca (superadmin, guru, industri).
 */
final class LogbookPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function baris(Logbook $logbook): array
    {
        return [
            'id' => $logbook->id,
            'tanggal' => $logbook->tanggal->toDateString(),
            'siswa' => $logbook->siswa->name,
            'kelompok' => $logbook->siswa->profilSiswa?->kelompok?->nama_kelompok,
            'aktivitas' => $logbook->aktivitas,
            'peralatan_software' => $logbook->peralatan_software,
            'sudah_dipahami' => $logbook->sudah_dipahami,
            'baru_ditemui' => $logbook->baru_ditemui,
            'kesulitan' => $logbook->kesulitan,
            'pengetahuan_sekolah_digunakan' => $logbook->pengetahuan_sekolah_digunakan,
            'ingin_dipelajari' => $logbook->ingin_dipelajari,
            'ada_bukti' => $logbook->bukti_kegiatan !== null,
            'sudah_dianalisis' => $logbook->hasil_analisis_ai !== null,
        ];
    }
}
