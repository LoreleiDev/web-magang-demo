<?php

namespace App\Http\Controllers\Guru;

use App\Enums\JenisDokumen;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnggahDokumenRequest;
use App\Models\DokumenKnowledgeBase;
use App\Models\ProgramKeahlian;
use App\Services\DokumenKnowledgeBaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Dokumen sekolah untuk knowledge base AI Mentor, per program keahlian guru (bagian 7 & 9.3).
 * File disimpan di server; pengiriman ke Gemini di Tahap 6.
 */
class DokumenSekolahController extends Controller
{
    public function __construct(private DokumenKnowledgeBaseService $dokumen) {}

    public function index(Request $request): Response
    {
        $program = $request->user()->programKeahlian();

        return Inertia::render('guru/dokumen', [
            'program' => $program ? ['kode' => $program, 'nama' => ProgramKeahlian::nama($program)] : null,
            'dokumen' => $program === null ? [] : DokumenKnowledgeBase::query()
                ->where('jenis', JenisDokumen::Sekolah)
                ->where('program_keahlian', $program)
                ->with('pengunggah:id,name')
                ->latest()
                ->get()
                ->map(fn (DokumenKnowledgeBase $d) => [
                    'id' => $d->id,
                    'nama_file' => $d->nama_file,
                    'diunggah_oleh' => $d->pengunggah->name,
                    'diunggah_pada' => $d->created_at?->toIso8601String(),
                ]),
            'aturanDokumen' => config('magang.dokumen_kb'),
        ]);
    }

    public function store(UnggahDokumenRequest $request): RedirectResponse
    {
        Gate::authorize('create', [DokumenKnowledgeBase::class, JenisDokumen::Sekolah]);

        /** @var list<UploadedFile> $files */
        $files = $request->file('dokumen');
        $program = (string) $request->user()->programKeahlian();

        foreach ($files as $file) {
            $this->dokumen->simpanSekolah($file, $program, $request->user());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => count($files).' dokumen berhasil diunggah.']);

        return back();
    }

    public function show(DokumenKnowledgeBase $dokumen): BinaryFileResponse
    {
        Gate::authorize('delete', $dokumen);

        return response()->download($this->dokumen->path($dokumen), $dokumen->nama_file);
    }

    public function destroy(DokumenKnowledgeBase $dokumen): RedirectResponse
    {
        Gate::authorize('delete', $dokumen);

        $this->dokumen->hapus($dokumen);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Dokumen {$dokumen->nama_file} dihapus."]);

        return back();
    }
}
