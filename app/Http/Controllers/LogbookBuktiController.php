<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bukti kegiatan logbook, hanya untuk yang berhak melihat logbook (bagian 2.1).
 */
class LogbookBuktiController extends Controller
{
    /** Jenis file yang boleh ditampilkan langsung di browser. */
    private const TAMPIL_LANGSUNG = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public function __invoke(Logbook $logbook): StreamedResponse
    {
        Gate::authorize('view', $logbook);

        $disk = Storage::disk('local');
        abort_if($logbook->bukti_kegiatan === null || ! $disk->exists($logbook->bukti_kegiatan), 404);

        $mime = (string) $disk->mimeType($logbook->bukti_kegiatan);
        $aman = in_array($mime, self::TAMPIL_LANGSUNG, true);

        // File lama/lain yang bukan gambar/PDF diunduh, tidak ditampilkan; header di bawah
        // mencegah browser menjalankan isi file sebagai halaman (XSS tersimpan).
        return $disk->response(
            $logbook->bukti_kegiatan,
            basename($logbook->bukti_kegiatan),
            [
                'Content-Type' => $aman ? $mime : 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            ],
            $aman ? 'inline' : 'attachment',
        );
    }
}
