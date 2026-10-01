<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\JenisDokumen;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnggahDokumenRequest;
use App\Models\DokumenKnowledgeBase;
use App\Models\Perusahaan;
use App\Services\DokumenKnowledgeBaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Dokumen industri per perusahaan, ditambah/dihapus lewat edit perusahaan (CLAUDE.md bagian 5.2).
 */
class DokumenIndustriController extends Controller
{
    public function __construct(private DokumenKnowledgeBaseService $dokumen) {}

    public function store(UnggahDokumenRequest $request, Perusahaan $perusahaan): RedirectResponse
    {
        Gate::authorize('create', [DokumenKnowledgeBase::class, JenisDokumen::Industri]);

        /** @var list<UploadedFile> $files */
        $files = $request->file('dokumen');

        foreach ($files as $file) {
            $this->dokumen->simpanIndustri($file, $perusahaan->id, $request->user());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => count($files).' dokumen berhasil diunggah.']);

        return back();
    }

    public function show(Perusahaan $perusahaan, DokumenKnowledgeBase $dokumen): BinaryFileResponse
    {
        $this->pastikanMilik($perusahaan, $dokumen);
        Gate::authorize('update', $perusahaan);

        return response()->download($this->dokumen->path($dokumen), $dokumen->nama_file);
    }

    public function destroy(Perusahaan $perusahaan, DokumenKnowledgeBase $dokumen): RedirectResponse
    {
        $this->pastikanMilik($perusahaan, $dokumen);
        Gate::authorize('delete', $dokumen);

        $this->dokumen->hapus($dokumen);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Dokumen {$dokumen->nama_file} dihapus."]);

        return back();
    }

    private function pastikanMilik(Perusahaan $perusahaan, DokumenKnowledgeBase $dokumen): void
    {
        abort_unless(
            $dokumen->jenis === JenisDokumen::Industri && $dokumen->perusahaan_id === $perusahaan->id,
            404,
        );
    }
}
