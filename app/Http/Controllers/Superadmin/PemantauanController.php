<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\HasilAssessment;
use App\Models\KelompokMagang;
use App\Models\Logbook;
use App\Models\Materi;
use App\Models\User;
use App\Support\MateriPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman baca-saja superadmin: materi, logbook, dan assessment seluruh siswa
 * (CLAUDE.md bagian 5.4, keputusan 13 no. 17).
 */
class PemantauanController extends Controller
{
    public function materi(Request $request): Response
    {
        $program = $this->filterProgram($request);

        $materi = Materi::query()
            ->with(['kompetensi', 'pembuat:id,name'])
            ->when($program, fn (Builder $q) => $q->whereHas('kompetensi', fn (Builder $q) => $q->where('program_keahlian', $program)))
            ->latest('diubah_terakhir')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Materi $m) => MateriPresenter::ringkas($m));

        return Inertia::render('superadmin/pemantauan/materi', [
            'materi' => $materi,
            'filter' => ['program' => $program],
        ]);
    }

    public function materiShow(Materi $materi): Response
    {
        Gate::authorize('view', $materi);

        return Inertia::render('superadmin/pemantauan/materi-detail', [
            'materi' => MateriPresenter::detail($materi, denganKunci: true),
            'riwayatVerifikasi' => MateriPresenter::riwayatVerifikasi($materi),
        ]);
    }

    public function logbook(Request $request): Response
    {
        $kelompokId = $request->integer('kelompok') ?: null;
        $cari = trim((string) $request->query('cari'));

        $logbook = Logbook::query()
            ->with('siswa.profilSiswa.kelompok:id,nama_kelompok')
            ->whereHas('siswa', fn (Builder $q) => $this->filterSiswa($q, $kelompokId, $cari))
            ->latest('tanggal')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Logbook $l) => [
                'id' => $l->id,
                'tanggal' => $l->tanggal->toDateString(),
                'siswa' => $l->siswa->name,
                'kelompok' => $l->siswa->profilSiswa?->kelompok?->nama_kelompok,
                'aktivitas' => $l->aktivitas,
                'peralatan_software' => $l->peralatan_software,
                'sudah_dipahami' => $l->sudah_dipahami,
                'baru_ditemui' => $l->baru_ditemui,
                'kesulitan' => $l->kesulitan,
                'pengetahuan_sekolah_digunakan' => $l->pengetahuan_sekolah_digunakan,
                'ingin_dipelajari' => $l->ingin_dipelajari,
                'ada_bukti' => $l->bukti_kegiatan !== null,
                'sudah_dianalisis' => $l->hasil_analisis_ai !== null,
            ]);

        return Inertia::render('superadmin/pemantauan/logbook', [
            'logbook' => $logbook,
            'filter' => ['kelompok' => $kelompokId, 'cari' => $cari],
            'kelompok' => $this->opsiKelompok(),
        ]);
    }

    public function assessment(Request $request): Response
    {
        $kelompokId = $request->integer('kelompok') ?: null;
        $cari = trim((string) $request->query('cari'));

        $hasil = HasilAssessment::query()
            ->with(['siswa.profilSiswa.kelompok:id,nama_kelompok', 'kuis.materi:id,judul'])
            ->whereHas('siswa', fn (Builder $q) => $this->filterSiswa($q, $kelompokId, $cari))
            ->latest('tanggal')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (HasilAssessment $h) => [
                'id' => $h->id,
                'tanggal' => $h->tanggal->toIso8601String(),
                'siswa' => $h->siswa->name,
                'kelompok' => $h->siswa->profilSiswa?->kelompok?->nama_kelompok,
                'materi' => $h->kuis->materi->judul,
                'skor' => $h->skor,
                'lulus' => $h->lulus,
            ]);

        return Inertia::render('superadmin/pemantauan/assessment', [
            'hasil' => $hasil,
            'filter' => ['kelompok' => $kelompokId, 'cari' => $cari],
            'kelompok' => $this->opsiKelompok(),
        ]);
    }

    /**
     * @param  Builder<User>  $query
     */
    private function filterSiswa(Builder $query, ?int $kelompokId, string $cari): void
    {
        $query->where('role', Role::Siswa)
            ->when($kelompokId, fn (Builder $q) => $q->whereHas('profilSiswa', fn (Builder $q) => $q->where('kelompok_id', $kelompokId)))
            ->when($cari !== '', fn (Builder $q) => $q->where('name', 'like', "%{$cari}%"));
    }

    private function filterProgram(Request $request): ?string
    {
        $program = (string) $request->query('program');

        return array_key_exists($program, config('magang.program_keahlian')) ? $program : null;
    }

    /**
     * @return list<array{id: int, nama: string}>
     */
    private function opsiKelompok(): array
    {
        return array_values(KelompokMagang::orderBy('nama_kelompok')->get(['id', 'nama_kelompok'])
            ->map(fn (KelompokMagang $k) => ['id' => $k->id, 'nama' => $k->nama_kelompok])
            ->all());
    }
}
