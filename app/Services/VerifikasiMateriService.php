<?php

namespace App\Services;

use App\Enums\HasilVerifikasiMateri;
use App\Jobs\KirimEmailMasukanMateri;
use App\Models\Materi;
use App\Models\User;
use App\Models\VerifikasiMateri;
use Illuminate\Support\Facades\DB;

/**
 * Verifikasi materi oleh industri (CLAUDE.md bagian 11.2 & 13.1).
 */
class VerifikasiMateriService
{
    /**
     * Simpan hasil pemeriksaan, hitung ulang badge, lalu kirim email ke guru
     * pembuat materi lewat queue. Email gagal tidak membatalkan verifikasi.
     */
    public function periksa(Materi $materi, User $industri, HasilVerifikasiMateri $hasil, ?string $masukan): VerifikasiMateri
    {
        $verifikasi = DB::transaction(function () use ($materi, $industri, $hasil, $masukan) {
            $verifikasi = $materi->verifikasi()->create([
                'diperiksa_oleh' => $industri->id,
                'nama_pemeriksa' => $industri->name,
                'perusahaan_id' => $industri->perusahaanId(),
                'hasil' => $hasil,
                'masukan' => filled($masukan) ? trim((string) $masukan) : null,
                'email_terkirim' => false,
            ]);

            $materi->update(['terverifikasi_industri' => $this->badgeTampil($materi)]);

            return $verifikasi;
        });

        KirimEmailMasukanMateri::dispatch($verifikasi)->afterCommit();

        return $verifikasi;
    }

    /**
     * Aturan badge (13.1): hanya hasil terakhir tiap perusahaan sejak materi terakhir
     * diedit yang dihitung. Badge tampil jika minimal satu "Diverifikasi" dan tidak
     * ada yang "Belum Sesuai".
     */
    public function badgeTampil(Materi $materi): bool
    {
        $terakhirPerPerusahaan = $materi->verifikasi()
            ->when($materi->diubah_terakhir, fn ($q) => $q->where('created_at', '>=', $materi->diubah_terakhir))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->unique('perusahaan_id');

        return $terakhirPerPerusahaan->contains('hasil', HasilVerifikasiMateri::Diverifikasi)
            && ! $terakhirPerPerusahaan->contains('hasil', HasilVerifikasiMateri::BelumSesuai);
    }
}
