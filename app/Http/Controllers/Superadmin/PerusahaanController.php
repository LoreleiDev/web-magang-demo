<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\PerusahaanRequest;
use App\Models\DokumenKnowledgeBase;
use App\Models\Perusahaan;
use App\Services\DokumenKnowledgeBaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Data perusahaan & dokumen industrinya (CLAUDE.md bagian 5.2).
 */
class PerusahaanController extends Controller
{
    public function __construct(private DokumenKnowledgeBaseService $dokumen) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', Perusahaan::class);

        $perusahaan = Perusahaan::query()
            ->withCount(['kelompokMagang', 'profilIndustri', 'dokumen'])
            ->orderBy('nama')
            ->get()
            ->map(fn (Perusahaan $p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'alamat' => $p->alamat,
                'bidang_usaha' => $p->bidang_usaha,
                'daftar_unit_kerja' => $p->daftar_unit_kerja,
                'jumlah_kelompok' => $p->kelompok_magang_count,
                'jumlah_akun_industri' => $p->profil_industri_count,
                'jumlah_dokumen' => $p->dokumen_count,
                'bisa_dihapus' => $p->kelompok_magang_count === 0 && $p->profil_industri_count === 0,
            ]);

        return Inertia::render('superadmin/perusahaan/index', [
            'perusahaan' => $perusahaan,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Perusahaan::class);

        return Inertia::render('superadmin/perusahaan/form', [
            'perusahaan' => null,
            'dokumen' => [],
            'aturanDokumen' => config('magang.dokumen_kb'),
        ]);
    }

    public function store(PerusahaanRequest $request): RedirectResponse
    {
        Gate::authorize('create', Perusahaan::class);

        $perusahaan = DB::transaction(function () use ($request) {
            $perusahaan = Perusahaan::create([
                ...$request->safe()->only(['nama', 'alamat', 'bidang_usaha']),
                'daftar_unit_kerja' => $request->unitKerja(),
            ]);

            /** @var list<UploadedFile> $files */
            $files = $request->file('dokumen', []);

            foreach ($files as $file) {
                $this->dokumen->simpanIndustri($file, $perusahaan->id, $request->user());
            }

            return $perusahaan;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Perusahaan {$perusahaan->nama} berhasil ditambahkan."]);

        return to_route('superadmin.perusahaan.index');
    }

    public function edit(Perusahaan $perusahaan): Response
    {
        Gate::authorize('update', $perusahaan);

        return Inertia::render('superadmin/perusahaan/form', [
            'perusahaan' => [
                'id' => $perusahaan->id,
                'nama' => $perusahaan->nama,
                'alamat' => $perusahaan->alamat,
                'bidang_usaha' => $perusahaan->bidang_usaha,
                'daftar_unit_kerja' => $perusahaan->daftar_unit_kerja,
            ],
            'dokumen' => $perusahaan->dokumen()->with('pengunggah:id,name')->latest()->get()
                ->map(fn (DokumenKnowledgeBase $d) => [
                    'id' => $d->id,
                    'nama_file' => $d->nama_file,
                    'diunggah_oleh' => $d->pengunggah->name ?? 'Akun dihapus',
                    'diunggah_pada' => $d->created_at?->toIso8601String(),
                    'status_ai' => $d->status_ai,
                    'pesan_error_ai' => $d->pesan_error_ai,
                ]),
            'aturanDokumen' => config('magang.dokumen_kb'),
        ]);
    }

    public function update(PerusahaanRequest $request, Perusahaan $perusahaan): RedirectResponse
    {
        Gate::authorize('update', $perusahaan);

        $perusahaan->update([
            ...$request->safe()->only(['nama', 'alamat', 'bidang_usaha']),
            'daftar_unit_kerja' => $request->unitKerja(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Data perusahaan disimpan.']);

        return to_route('superadmin.perusahaan.index');
    }

    /**
     * Perusahaan yang masih dipakai kelompok magang atau akun industri tidak bisa dihapus.
     */
    public function destroy(Perusahaan $perusahaan): RedirectResponse
    {
        Gate::authorize('delete', $perusahaan);

        if ($perusahaan->kelompokMagang()->exists() || $perusahaan->profilIndustri()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Perusahaan ini masih dipakai kelompok magang atau akun industri, jadi belum bisa dihapus.',
            ]);

            return back();
        }

        DB::transaction(function () use ($perusahaan) {
            $perusahaan->dokumen->each(fn (DokumenKnowledgeBase $d) => $this->dokumen->hapus($d));
            $perusahaan->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Perusahaan {$perusahaan->nama} dihapus."]);

        return to_route('superadmin.perusahaan.index');
    }
}
