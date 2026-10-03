<?php

namespace App\Support;

use App\Models\Kompetensi;
use App\Services\ProgresKompetensiService;

/**
 * Bentuk satu baris peta kompetensi siswa untuk frontend.
 *
 * @phpstan-import-type BarisPeta from ProgresKompetensiService
 */
final class KompetensiPresenter
{
    /**
     * @param  BarisPeta  $baris
     * @return array<string, mixed>
     */
    public static function baris(array $baris): array
    {
        $k = $baris['kompetensi'];
        $p = $baris['progres'];

        return [
            'kompetensi_id' => $k->id,
            'progres_id' => $p?->id,
            'nama_kompetensi_sekolah' => $k->nama_kompetensi_sekolah,
            'aktivitas_kompetensi_industri' => $k->aktivitas_kompetensi_industri,
            'target_level' => $k->target_level,
            'level' => $baris['level'],
            'level_diisi' => $baris['level_diisi'],
            'gap' => $baris['gap'],
            'warna' => $baris['warna'],
            'status' => $baris['status']->value,
            'status_label' => $baris['status']->label(),
            'level_diisi_pada' => $p?->tanggal_level_diisi?->toIso8601String(),
            'diverifikasi_oleh' => $p->nama_verifikator ?? $p?->verifikator?->name,
            'diverifikasi_pada' => $p?->tanggal_verifikasi?->toIso8601String(),
        ];
    }
}
