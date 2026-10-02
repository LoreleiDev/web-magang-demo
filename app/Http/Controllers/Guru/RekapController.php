<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\KelompokMagang;
use App\Models\User;
use App\Services\ProgresKompetensiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rekap Kelompok (keputusan 13 no. 31): learning gap (siswa × kompetensi) dan
 * progres semua siswa di kelompok yang dibimbing guru.
 */
class RekapController extends Controller
{
    public function __invoke(Request $request, ProgresKompetensiService $progres): Response
    {
        $guru = $request->user();

        $kelompok = $guru->kelompokDibimbing()->with('perusahaan:id,nama')->orderByDesc('periode_mulai')->get();
        $terpilih = $kelompok->firstWhere('id', $request->integer('kelompok')) ?? $kelompok->first();

        $siswa = $terpilih
            ? $terpilih->siswa()->with('profilSiswa.kompetensiFokus')->orderBy('name')->get()
            : collect();

        // Kelompok bisa berisi beberapa program keahlian: kolom kompetensi dikelompokkan per program.
        $baris = $siswa->map(function (User $s) use ($progres) {
            $peta = $progres->peta($s);

            return [
                'id' => $s->id,
                'nama' => $s->name,
                'program_keahlian' => $s->profilSiswa?->program_keahlian,
                'kompetensi_utama_id' => $s->profilSiswa?->kompetensi_fokus_id,
                'ringkasan' => $progres->ringkasan($s),
                'sel' => $peta->mapWithKeys(fn (array $b) => [$b['kompetensi']->id => [
                    'level' => $b['level'],
                    'target' => $b['kompetensi']->target_level,
                    'gap' => $b['gap'],
                    'warna' => $b['warna'],
                    'status' => $b['status']->value,
                ]]),
                'kolom' => $peta->map(fn (array $b) => [
                    'id' => $b['kompetensi']->id,
                    'nama' => $b['kompetensi']->nama_kompetensi_sekolah,
                    'target' => $b['kompetensi']->target_level,
                ])->values(),
            ];
        });

        $program = $baris->groupBy('program_keahlian')->map(fn ($daftar, $kode) => [
            'kode' => (string) $kode,
            'kompetensi' => $daftar->first()['kolom'],
            'siswa' => $daftar->map(fn (array $b) => collect($b)->except('kolom'))->values(),
        ])->values();

        return Inertia::render('guru/rekap', [
            'kelompok' => $kelompok->map(fn (KelompokMagang $k) => [
                'id' => $k->id,
                'nama_kelompok' => $k->nama_kelompok,
                'perusahaan' => $k->perusahaan->nama,
            ]),
            'kelompokTerpilih' => $terpilih?->id,
            'program' => $program,
        ]);
    }
}
