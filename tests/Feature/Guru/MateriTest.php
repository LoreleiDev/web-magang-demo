<?php

use App\Models\HasilAssessment;
use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->guru = User::factory()->guru('TKJ')->create();
    $this->kompetensi = Kompetensi::factory()->create(['program_keahlian' => 'TKJ']);
});

function dataMateri(array $timpa = []): array
{
    $langkah = array_fill(0, 8, ['konten_teks' => 'Isi langkah', 'media' => []]);
    $langkah[2]['media'] = [['jenis' => 'youtube', 'url' => 'https://youtu.be/ecCuyq-Wprc', 'keterangan' => 'Video']];
    $langkah[0]['media'] = [['jenis' => 'dokumen', 'url' => 'https://docs.google.com/document/d/1AbCdEfGhIjKlMnOp/edit', 'keterangan' => '']];

    return [
        'kompetensi_id' => test()->kompetensi->id,
        'judul' => 'Subnetting dasar',
        'langkah' => $langkah,
        'soal' => [[
            'pertanyaan' => '/27 punya berapa host?',
            'pilihan' => ['30', '32'],
            'jawaban_benar' => 0,
            'pembahasan' => '2^5 - 2',
        ]],
        ...$timpa,
    ];
}

test('guru membuat materi 8 langkah dengan media link dan kuis', function () {
    $this->actingAs($this->guru)
        ->post(route('guru.materi.store'), dataMateri())
        ->assertRedirect(route('guru.materi.index'));

    $materi = Materi::with('langkah.media', 'kuis.soal')->firstOrFail();

    expect($materi->dibuat_oleh)->toBe($this->guru->id)
        ->and($materi->terverifikasi_industri)->toBeFalse()
        ->and($materi->langkah)->toHaveCount(8)
        ->and($materi->langkah[2]->media[0]->id_media)->toBe('ecCuyq-Wprc')
        ->and($materi->langkah[0]->media[0]->id_media)->toBe('1AbCdEfGhIjKlMnOp')
        ->and($materi->kuis->soal)->toHaveCount(1)
        ->and($materi->kuis->soal[0]->jawaban_benar)->toBe(0);
});

test('link media divalidasi sesuai jenisnya', function () {
    $data = dataMateri();
    $data['langkah'][2]['media'] = [['jenis' => 'youtube', 'url' => 'https://vimeo.com/123', 'keterangan' => '']];
    $data['langkah'][4]['media'] = [['jenis' => 'gambar', 'url' => 'https://youtu.be/ecCuyq-Wprc', 'keterangan' => '']];

    $this->actingAs($this->guru)
        ->post(route('guru.materi.store'), $data)
        ->assertSessionHasErrors(['langkah.2.media.0.url', 'langkah.4.media.0.url']);

    expect(Materi::count())->toBe(0);
});

test('validasi materi: 8 langkah, minimal 1 soal, jawaban benar harus ada di pilihan', function () {
    $this->actingAs($this->guru)
        ->post(route('guru.materi.store'), dataMateri([
            'langkah' => array_fill(0, 3, ['konten_teks' => 'x']),
            'soal' => [],
        ]))
        ->assertSessionHasErrors(['langkah', 'soal']);

    $this->actingAs($this->guru)
        ->post(route('guru.materi.store'), dataMateri([
            'soal' => [['pertanyaan' => 'A?', 'pilihan' => ['1', '2'], 'jawaban_benar' => 4]],
        ]))
        ->assertSessionHasErrors('soal.0.jawaban_benar');
});

test('materi hanya untuk kompetensi program keahlian guru', function () {
    $rpl = Kompetensi::factory()->create(['program_keahlian' => 'RPL']);

    $this->actingAs($this->guru)
        ->post(route('guru.materi.store'), dataMateri(['kompetensi_id' => $rpl->id]))
        ->assertSessionHasErrors('kompetensi_id');
});

test('edit materi mengganti langkah & soal tetapi riwayat kuis tetap', function () {
    $this->actingAs($this->guru)->post(route('guru.materi.store'), dataMateri());
    $materi = Materi::with('kuis')->firstOrFail();
    $siswa = User::factory()->siswa()->create();
    HasilAssessment::create(['siswa_id' => $siswa->id, 'kuis_id' => $materi->kuis->id, 'skor' => 80, 'lulus' => true, 'tanggal' => now()]);

    $data = dataMateri(['judul' => 'Subnetting lanjutan']);
    $data['langkah'][2]['media'] = [];
    $data['soal'][] = ['pertanyaan' => 'Soal 2?', 'pilihan' => ['a', 'b', 'c'], 'jawaban_benar' => 2, 'pembahasan' => null];

    $this->actingAs($this->guru)
        ->put(route('guru.materi.update', $materi), $data)
        ->assertSessionHasNoErrors();

    $materi->refresh()->load('langkah.media', 'kuis.soal');

    expect($materi->judul)->toBe('Subnetting lanjutan')
        ->and($materi->langkah[2]->media)->toHaveCount(0)
        ->and($materi->kuis->soal)->toHaveCount(2)
        ->and(HasilAssessment::count())->toBe(1);
});

test('materi program lain tidak bisa diedit, tetapi bisa dipratinjau', function () {
    $materiRpl = Materi::factory()->create(['kompetensi_id' => Kompetensi::factory()->create(['program_keahlian' => 'RPL'])->id]);

    $this->actingAs($this->guru)->get(route('guru.materi.edit', $materiRpl))->assertForbidden();
    $this->actingAs($this->guru)->put(route('guru.materi.update', $materiRpl), dataMateri())->assertForbidden();
    $this->actingAs($this->guru)
        ->get(route('guru.materi.show', $materiRpl))
        ->assertInertia(fn (Assert $page) => $page->where('bisaEdit', false));
});

test('daftar materi menampilkan masukan terakhir industri', function () {
    $materi = Materi::factory()->create(['kompetensi_id' => $this->kompetensi->id, 'dibuat_oleh' => $this->guru->id]);
    $industri = User::factory()->industri()->create();
    $materi->verifikasi()->create([
        'diperiksa_oleh' => $industri->id,
        'perusahaan_id' => $industri->perusahaanId(),
        'hasil' => 'belum_sesuai',
        'masukan' => 'Tambahkan contoh VLSM.',
    ]);

    $this->actingAs($this->guru)
        ->get(route('guru.materi.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('materi.0.masukan_terakhir.masukan', 'Tambahkan contoh VLSM.')
            ->where('materi.0.terverifikasi_industri', false));
});
