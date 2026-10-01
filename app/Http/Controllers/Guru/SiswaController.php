<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\IsiLevelRequest;
use App\Models\Kompetensi;
use App\Models\ProgresKompetensi;
use App\Models\User;
use App\Services\ProgresKompetensiService;
use App\Services\RingkasanSiswaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Detail siswa di kelompok bimbingan guru + pengisian level kompetensi (bagian 7 & 11.1).
 */
class SiswaController extends Controller
{
    public function show(User $siswa, RingkasanSiswaService $ringkasan): Response
    {
        Gate::authorize('lihatSiswa', $siswa);

        return Inertia::render('guru/siswa', $ringkasan->detail($siswa));
    }

    /**
     * Isi/ubah level (1-4) satu kompetensi siswa.
     */
    public function isiLevel(IsiLevelRequest $request, User $siswa, Kompetensi $kompetensi, ProgresKompetensiService $progres): RedirectResponse
    {
        // Kompetensi harus dari program keahlian siswa tsb.
        abort_unless($kompetensi->program_keahlian === $siswa->programKeahlian(), 404);

        $baris = ProgresKompetensi::firstOrNew(['siswa_id' => $siswa->id, 'kompetensi_id' => $kompetensi->id]);
        $baris->setRelation('siswa', $siswa);
        Gate::authorize('isiLevel', $baris);

        $hasil = $progres->isiLevel($siswa, $kompetensi, $request->integer('level_siswa'), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Level {$kompetensi->nama_kompetensi_sekolah} disimpan: {$hasil->level_siswa}. Status: {$hasil->status->label()}.",
        ]);

        return back();
    }
}
