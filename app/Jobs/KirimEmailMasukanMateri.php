<?php

namespace App\Jobs;

use App\Mail\MasukanMateriMail;
use App\Models\VerifikasiMateri;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Kirim hasil & masukan verifikasi materi ke guru pembuat materi (bagian 11.2).
 * Jika gagal setelah semua percobaan, `email_terkirim` tetap false dan masukan
 * tetap bisa dibaca guru di Manajemen Materi.
 */
class KirimEmailMasukanMateri implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public VerifikasiMateri $verifikasi) {}

    public function handle(): void
    {
        $this->verifikasi->loadMissing(['materi.pembuat', 'pemeriksa', 'perusahaan']);

        // Akun guru pembuat sudah dihapus: masukan tetap tersimpan di sistem, email tidak dikirim.
        if ($this->verifikasi->materi->pembuat === null) {
            return;
        }

        Mail::to($this->verifikasi->materi->pembuat)->send(new MasukanMateriMail($this->verifikasi));

        $this->verifikasi->update(['email_terkirim' => true]);
    }

    public function failed(?\Throwable $e): void
    {
        $this->verifikasi->update(['email_terkirim' => false]);
    }
}
