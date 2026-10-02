<?php

namespace App\Http\Controllers\Guru;

use App\Enums\JenisLangkah;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\MateriRequest;
use App\Models\Kompetensi;
use App\Models\LangkahMateri;
use App\Models\Materi;
use App\Models\MediaMateri;
use App\Models\ProgramKeahlian;
use App\Models\SoalKuis;
use App\Models\VerifikasiMateri;
use App\Services\MateriService;
use App\Support\MateriPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manajemen materi 8 langkah + kuis untuk program keahlian guru (bagian 7).
 * Daftar menampilkan badge verifikasi dan masukan terakhir industri (bagian 11.2).
 */
class MateriController extends Controller
{
    public function __construct(private MateriService $materi) {}

    public function index(Request $request): Response
    {
        $program = $request->user()->programKeahlian();

        $materi = $program === null ? collect() : Materi::query()
            ->whereHas('kompetensi', fn (Builder $q) => $q->where('program_keahlian', $program))
            ->with(['kompetensi.program', 'pembuat:id,name'])
            ->with(['verifikasi' => fn ($q) => $q->with(['pemeriksa:id,name', 'perusahaan:id,nama'])->latest()])
            ->latest('diubah_terakhir')
            ->get()
            ->map(function (Materi $m) {
                /** @var VerifikasiMateri|null $terakhir */
                $terakhir = $m->verifikasi->first();

                return [
                    ...MateriPresenter::ringkas($m),
                    'masukan_terakhir' => $terakhir ? [
                        'hasil' => $terakhir->hasil->value,
                        'hasil_label' => $terakhir->hasil->label(),
                        'masukan' => $terakhir->masukan,
                        'pemeriksa' => $terakhir->pemeriksa->name,
                        'perusahaan' => $terakhir->perusahaan->nama,
                        'tanggal' => $terakhir->created_at?->toIso8601String(),
                    ] : null,
                ];
            });

        return Inertia::render('guru/materi/index', [
            'program' => $program ? ['kode' => $program, 'nama' => ProgramKeahlian::nama($program)] : null,
            'materi' => $materi,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Materi::class);

        return Inertia::render('guru/materi/form', [
            'materi' => null,
            ...$this->opsi($request),
            'riwayatVerifikasi' => [],
        ]);
    }

    public function store(MateriRequest $request): RedirectResponse
    {
        Gate::authorize('create', Materi::class);

        $materi = $this->materi->simpan(null, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Materi \"{$materi->judul}\" disimpan."]);

        return to_route('guru.materi.index');
    }

    /**
     * Pratinjau materi seperti yang dibaca siswa (dengan kunci jawaban).
     */
    public function show(Request $request, Materi $materi): Response
    {
        Gate::authorize('view', $materi);

        return Inertia::render('guru/materi/show', [
            'materi' => MateriPresenter::detail($materi, denganKunci: true),
            'riwayatVerifikasi' => MateriPresenter::riwayatVerifikasi($materi),
            'bisaEdit' => $request->user()->can('update', $materi),
        ]);
    }

    public function edit(Request $request, Materi $materi): Response
    {
        Gate::authorize('update', $materi);

        $materi->load(['langkah.media', 'kuis.soal']);

        return Inertia::render('guru/materi/form', [
            'materi' => [
                'id' => $materi->id,
                'kompetensi_id' => $materi->kompetensi_id,
                'judul' => $materi->judul,
                'level' => $materi->level,
                'nilai_minimal' => $materi->nilai_minimal,
                'terverifikasi_industri' => $materi->terverifikasi_industri,
                'langkah' => $materi->langkah->map(fn (LangkahMateri $l) => [
                    'konten_teks' => $l->konten_teks ?? '',
                    'media' => $l->media->map(fn (MediaMateri $m) => [
                        'jenis' => $m->jenis->value,
                        'url' => $m->url,
                        'keterangan' => $m->keterangan ?? '',
                    ])->values(),
                ])->values(),
                'soal' => $materi->kuis?->soal->map(fn (SoalKuis $s) => [
                    'pertanyaan' => $s->pertanyaan,
                    'pilihan' => $s->pilihan,
                    'jawaban_benar' => $s->jawaban_benar,
                    'pembahasan' => $s->pembahasan ?? '',
                ])->values() ?? [],
            ],
            ...$this->opsi($request),
            'riwayatVerifikasi' => MateriPresenter::riwayatVerifikasi($materi),
        ]);
    }

    public function update(MateriRequest $request, Materi $materi): RedirectResponse
    {
        Gate::authorize('update', $materi);

        $this->materi->simpan($materi, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perubahan materi disimpan.']);

        return to_route('guru.materi.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function opsi(Request $request): array
    {
        $program = $request->user()->programKeahlian();

        return [
            'program' => ProgramKeahlian::nama($program),
            'kompetensi' => Kompetensi::query()
                ->where('program_keahlian', $program)
                ->orderBy('id')
                ->get(['id', 'nama_kompetensi_sekolah'])
                ->map(fn (Kompetensi $k) => ['id' => $k->id, 'nama' => $k->nama_kompetensi_sekolah]),
            'judulLangkah' => array_map(fn (JenisLangkah $j) => $j->label(), JenisLangkah::cases()),
        ];
    }
}
