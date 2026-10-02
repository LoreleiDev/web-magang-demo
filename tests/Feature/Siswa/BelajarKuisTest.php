<?php

use App\Enums\StatusKompetensi;
use App\Models\HasilAssessment;
use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\ProgresKompetensi;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Materi dengan 2 soal; jawaban benar soal pertama = 0, soal kedua = 1.
 */
function materiDenganKuis(Kompetensi $kompetensi, int $level, int $nilaiMinimal = 75): Materi
{
    $materi = Materi::factory()->create([
        'kompetensi_id' => $kompetensi->id,
        'level' => $level,
        'nilai_minimal' => $nilaiMinimal,
    ]);
    $kuis = $materi->kuis()->create();
    $kuis->soal()->create(['urutan' => 1, 'pertanyaan' => 'A?', 'pilihan' => ['benar', 'salah'], 'jawaban_benar' => 0, 'pembahasan' => 'Karena A']);
    $kuis->soal()->create(['urutan' => 2, 'pertanyaan' => 'B?', 'pilihan' => ['salah', 'benar'], 'jawaban_benar' => 1]);

    return $materi->load('kuis.soal');
}

/** @return array<int, int> */
function jawaban(Materi $materi, bool $semuaBenar): array
{
    return $materi->kuis->soal->mapWithKeys(fn ($s) => [
        $s->id => $semuaBenar ? $s->jawaban_benar : 1 - $s->jawaban_benar,
    ])->all();
}

beforeEach(function () {
    $this->kelompok = KelompokMagang::factory()->create();
    $this->siswa = User::factory()->siswa($this->kelompok, 'TKJ', 'Jaringan')->create();
    $this->kompetensi = Kompetensi::factory()->create(['program_keahlian' => 'TKJ', 'target_level' => 3]);

    siswaSiap($this->siswa, $this->kompetensi->id);
    $this->masuk = fn () => $this->actingAs($this->siswa);
});

test('membuka materi menandai kompetensi "Sedang dipelajari" tanpa mengirim kunci jawaban', function () {
    $materi = materiDenganKuis($this->kompetensi, 2);

    ($this->masuk)()
        ->get(route('siswa.belajar.show', $materi))
        ->assertInertia(fn (Assert $page) => $page
            ->component('siswa/belajar/show')
            ->has('materi.soal', 2)
            ->missing('materi.soal.0.jawaban_benar')
            ->missing('materi.soal.0.pembahasan'));

    $progres = ProgresKompetensi::where('siswa_id', $this->siswa->id)->firstOrFail();
    expect($progres->status)->toBe(StatusKompetensi::SedangDipelajari)
        ->and($progres->level_siswa)->toBeNull();
});

test('lulus kuis menaikkan level ke level materi dan status mengikuti', function () {
    $level2 = materiDenganKuis($this->kompetensi, 2);
    $level3 = materiDenganKuis($this->kompetensi, 3);

    ($this->masuk)()
        ->post(route('siswa.belajar.kuis', $level2), ['jawaban' => jawaban($level2, true)])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('hasilKuis.skor', 100)
        ->assertInertiaFlash('hasilKuis.lulus', true)
        ->assertInertiaFlash('hasilKuis.level_naik', 2);

    $progres = ProgresKompetensi::where('siswa_id', $this->siswa->id)->firstOrFail();
    expect($progres->level_siswa)->toBe(2)
        ->and($progres->status)->toBe(StatusKompetensi::SedangDipraktikkan)
        ->and($progres->tanggal_level_diisi)->not->toBeNull();

    // Level 3 = target -> Menunggu verifikasi.
    ($this->masuk)()->post(route('siswa.belajar.kuis', $level3), ['jawaban' => jawaban($level3, true)]);
    expect($progres->fresh()->level_siswa)->toBe(3)
        ->and($progres->fresh()->status)->toBe(StatusKompetensi::MenungguVerifikasi);
});

test('gagal kuis tidak menaikkan level; level tidak pernah turun', function () {
    $level3 = materiDenganKuis($this->kompetensi, 3);
    $level2 = materiDenganKuis($this->kompetensi, 2);

    ($this->masuk)()
        ->post(route('siswa.belajar.kuis', $level3), ['jawaban' => jawaban($level3, false)])
        ->assertInertiaFlash('hasilKuis.lulus', false)
        ->assertInertiaFlash('hasilKuis.skor', 0);

    $progres = ProgresKompetensi::where('siswa_id', $this->siswa->id)->firstOrFail();
    expect($progres->level_siswa)->toBeNull()
        ->and($progres->status)->toBe(StatusKompetensi::SedangDipelajari)
        ->and(HasilAssessment::where('lulus', false)->count())->toBe(1);

    ($this->masuk)()->post(route('siswa.belajar.kuis', $level3), ['jawaban' => jawaban($level3, true)]);
    ($this->masuk)()->post(route('siswa.belajar.kuis', $level2), ['jawaban' => jawaban($level2, true)]);

    expect($progres->fresh()->level_siswa)->toBe(3);
});

test('nilai minimal mengikuti materi, bukan angka tetap 75', function () {
    $materi = materiDenganKuis($this->kompetensi, 2, nilaiMinimal: 50);

    // 1 dari 2 benar = 50.
    $setengah = jawaban($materi, true);
    $setengah[array_key_last($setengah)] = 0;

    ($this->masuk)()
        ->post(route('siswa.belajar.kuis', $materi), ['jawaban' => $setengah])
        ->assertInertiaFlash('hasilKuis.skor', 50)
        ->assertInertiaFlash('hasilKuis.lulus', true);
});

test('semua soal wajib dijawab', function () {
    $materi = materiDenganKuis($this->kompetensi, 2);
    $soalPertama = $materi->kuis->soal->first();

    ($this->masuk)()
        ->post(route('siswa.belajar.kuis', $materi), ['jawaban' => [$soalPertama->id => 0]])
        ->assertSessionHasErrors();

    expect(HasilAssessment::count())->toBe(0);
});

test('terverifikasi tidak berubah walau lulus kuis lagi', function () {
    $materi = materiDenganKuis($this->kompetensi, 3);
    ProgresKompetensi::create([
        'siswa_id' => $this->siswa->id,
        'kompetensi_id' => $this->kompetensi->id,
        'level_siswa' => 3,
        'status' => StatusKompetensi::Terverifikasi,
    ]);

    ($this->masuk)()->post(route('siswa.belajar.kuis', $materi), ['jawaban' => jawaban($materi, true)]);

    expect(ProgresKompetensi::firstOrFail()->status)->toBe(StatusKompetensi::Terverifikasi);
});

test('materi program keahlian lain tidak bisa dibuka atau dikerjakan', function () {
    $materiRpl = materiDenganKuis(Kompetensi::factory()->create(['program_keahlian' => 'RPL']), 2);

    ($this->masuk)()->get(route('siswa.belajar.show', $materiRpl))->assertForbidden();
    ($this->masuk)()
        ->post(route('siswa.belajar.kuis', $materiRpl), ['jawaban' => jawaban($materiRpl, true)])
        ->assertForbidden();
});

test('daftar belajar, assessment, peta, learning gap, dan progress tampil', function () {
    $materi = materiDenganKuis($this->kompetensi, 2);
    ($this->masuk)()->post(route('siswa.belajar.kuis', $materi), ['jawaban' => jawaban($materi, false)]);

    ($this->masuk)()->get(route('siswa.belajar.index'))
        ->assertInertia(fn (Assert $page) => $page->has('kompetensi.0.materi', 1)
            ->where('kompetensi.0.materi.0.status_kuis.percobaan', 1));

    ($this->masuk)()->get(route('siswa.assessment'))
        ->assertInertia(fn (Assert $page) => $page->where('kuis.0.terbaik', 0)->where('kuis.0.lulus', false)->has('riwayat', 1));

    ($this->masuk)()->get(route('siswa.peta'))
        ->assertInertia(fn (Assert $page) => $page->where('kompetensi.0.level', 1)->where('kompetensi.0.gap', 2));

    ($this->masuk)()->get(route('siswa.gap'))
        ->assertInertia(fn (Assert $page) => $page->where('kompetensi.0.materi_disarankan.materi_id', $materi->id));

    ($this->masuk)()->get(route('siswa.progress'))
        ->assertInertia(fn (Assert $page) => $page->has('skor', 1)->where('ringkasan.total', 1));
});

test('dashboard: hari ke-, aktivitas hari ini, dan rekomendasi', function () {
    $materi = materiDenganKuis($this->kompetensi, 2);
    $this->kelompok->update(['periode_mulai' => now()->subDays(9)->toDateString()]);

    ($this->masuk)()->get(route('siswa.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('siswa/dashboard')
            ->where('konteks.hari_ke', 10)
            ->where('konteks.status_magang', 'berjalan')
            ->where('aktivitasHariIni.0.jenis', 'logbook')
            ->where('aktivitasHariIni.1.materi_id', $materi->id)
            ->where('rekomendasi.0.materi_id', $materi->id));
});
