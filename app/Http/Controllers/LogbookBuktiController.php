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
    public function __invoke(Logbook $logbook): StreamedResponse
    {
        Gate::authorize('view', $logbook);

        abort_if($logbook->bukti_kegiatan === null || ! Storage::disk('local')->exists($logbook->bukti_kegiatan), 404);

        return Storage::disk('local')->response($logbook->bukti_kegiatan);
    }
}
