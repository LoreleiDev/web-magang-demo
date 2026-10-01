<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Kompetensi;
use App\Models\ProgramKeahlian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manajemen kompetensi per program keahlian guru (bagian 7, keputusan 13 no. 16).
 * Belum ada fitur hapus.
 */
class KompetensiController extends Controller
{
    public function index(Request $request): Response
    {
        $program = $request->user()->programKeahlian();

        return Inertia::render('guru/kompetensi/index', [
            'program' => $program ? ['kode' => $program, 'nama' => ProgramKeahlian::nama($program)] : null,
            'kompetensi' => $program === null ? [] : Kompetensi::query()
                ->where('program_keahlian', $program)
                ->with('pembuat:id,name')
                ->withCount('materi')
                ->orderBy('id')
                ->get()
                ->map(fn (Kompetensi $k) => [
                    'id' => $k->id,
                    'nama_kompetensi_sekolah' => $k->nama_kompetensi_sekolah,
                    'aktivitas_kompetensi_industri' => $k->aktivitas_kompetensi_industri,
                    'target_level' => $k->target_level,
                    'pembuat' => $k->pembuat->name,
                    'jumlah_materi' => $k->materi_count,
                ]),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Kompetensi::class);

        return Inertia::render('guru/kompetensi/form', [
            'kompetensi' => null,
            'program' => ProgramKeahlian::nama($request->user()->programKeahlian()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Kompetensi::class);

        Kompetensi::create([
            ...$this->validasi($request),
            'program_keahlian' => $request->user()->programKeahlian(),
            'dibuat_oleh' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kompetensi ditambahkan.']);

        return to_route('guru.kompetensi.index');
    }

    public function edit(Request $request, Kompetensi $kompetensi): Response
    {
        Gate::authorize('update', $kompetensi);

        return Inertia::render('guru/kompetensi/form', [
            'kompetensi' => $kompetensi->only(['id', 'nama_kompetensi_sekolah', 'aktivitas_kompetensi_industri', 'target_level']),
            'program' => ProgramKeahlian::nama($kompetensi->program_keahlian),
        ]);
    }

    public function update(Request $request, Kompetensi $kompetensi): RedirectResponse
    {
        Gate::authorize('update', $kompetensi);

        $kompetensi->update($this->validasi($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kompetensi diperbarui.']);

        return to_route('guru.kompetensi.index');
    }

    /**
     * @return array{nama_kompetensi_sekolah: string, aktivitas_kompetensi_industri: string, target_level: int}
     */
    private function validasi(Request $request): array
    {
        /** @var array{nama_kompetensi_sekolah: string, aktivitas_kompetensi_industri: string, target_level: int} */
        return $request->validate([
            'nama_kompetensi_sekolah' => ['required', 'string', 'max:255'],
            'aktivitas_kompetensi_industri' => ['required', 'string', 'max:2000'],
            'target_level' => ['required', 'integer', 'between:1,4'],
        ]);
    }
}
