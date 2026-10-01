<?php

namespace Database\Seeders;

use App\Enums\JenisLangkah;
use App\Enums\JenisMedia;
use App\Models\HasilAssessment;
use App\Models\Kompetensi;
use App\Models\Kuis;
use App\Models\Materi;
use App\Models\User;
use App\Support\MediaLink;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Dua materi contoh lengkap: 8 langkah, media link (YouTube & Google Drive), dan kuis.
 *
 * Link Google Drive di sini adalah CONTOH (ID file tidak nyata). Ganti dengan link
 * file Drive milik sekolah yang diatur "Siapa saja yang memiliki link dapat melihat".
 */
class MateriSeeder extends Seeder
{
    /**
     * @param  list<array{0: User, 1: string, 2: int}>  $percobaan
     */
    public function run(Kompetensi $kompetensiSubnetting, Kompetensi $kompetensiGit, array $percobaan = []): void
    {
        $materi = [
            'tkj' => $this->buat($kompetensiSubnetting, 'Subnetting IPv4 untuk Perencanaan Jaringan Pelanggan', $this->langkahSubnetting(), $this->soalSubnetting()),
            'rpl' => $this->buat($kompetensiGit, 'Alur Kerja Git: Branch, Commit, dan Pull Request', $this->langkahGit(), $this->soalGit()),
        ];

        foreach ($percobaan as [$siswa, $kunci, $skor]) {
            HasilAssessment::create([
                'siswa_id' => $siswa->id,
                'kuis_id' => $materi[$kunci]->kuis->id,
                'skor' => $skor,
                'lulus' => $skor >= config('magang.skor_lulus'),
                'tanggal' => Carbon::parse('2026-09-28 15:00'),
            ]);
        }
    }

    /**
     * @param  array<string, array{0: string, 1?: list<array{0: JenisMedia, 1: string, 2?: string}>}>  $langkah
     * @param  list<array{0: string, 1: list<string>, 2: int, 3: string}>  $soal
     */
    private function buat(Kompetensi $kompetensi, string $judul, array $langkah, array $soal): Materi
    {
        $materi = Materi::create([
            'kompetensi_id' => $kompetensi->id,
            'judul' => $judul,
            'dibuat_oleh' => $kompetensi->dibuat_oleh,
            'diubah_terakhir' => Carbon::parse('2026-09-20 09:00'),
            'terverifikasi_industri' => false,
        ]);

        foreach (JenisLangkah::cases() as $jenis) {
            [$teks, $media] = $langkah[$jenis->value] + [1 => []];

            $baris = $materi->langkah()->create([
                'urutan' => $jenis->urutan(),
                'jenis' => $jenis,
                'konten_teks' => $teks,
            ]);

            foreach ($media as $i => $m) {
                $baris->media()->create([
                    'urutan' => $i + 1,
                    'jenis' => $m[0],
                    'url' => $m[1],
                    'id_media' => MediaLink::ambilId($m[0], $m[1]),
                    'keterangan' => $m[2] ?? null,
                ]);
            }
        }

        /** @var Kuis $kuis */
        $kuis = $materi->kuis()->create();

        foreach ($soal as $i => [$pertanyaan, $pilihan, $benar, $pembahasan]) {
            $kuis->soal()->create([
                'urutan' => $i + 1,
                'pertanyaan' => $pertanyaan,
                'pilihan' => $pilihan,
                'jawaban_benar' => $benar,
                'pembahasan' => $pembahasan,
            ]);
        }

        return $materi->load('kuis');
    }

    /**
     * @return array<string, array{0: string, 1?: list<array{0: JenisMedia, 1: string, 2?: string}>}>
     */
    private function langkahSubnetting(): array
    {
        return [
            'konsep_dasar' => [
                "Alamat IPv4 terdiri dari 32 bit yang dibagi menjadi bagian network dan bagian host. Subnet mask (misalnya /24 atau 255.255.255.0) menunjukkan berapa bit yang dipakai untuk network.\n\nSubnetting adalah membagi satu jaringan besar menjadi beberapa jaringan kecil. Rumus penting:\n- Jumlah alamat per subnet = 2^(32 - prefix)\n- Jumlah host yang bisa dipakai = jumlah alamat - 2 (alamat network dan broadcast)\n\nContoh: /27 berarti 2^5 = 32 alamat, 30 host yang bisa dipakai.",
                [[JenisMedia::Dokumen, 'https://drive.google.com/file/d/1CONTOHtabelSubnetIPv4xx/view?usp=sharing', 'Tabel cepat prefix /24 sampai /30']],
            ],
            'contoh_industri' => [
                "Di ISP, setiap pelanggan bisnis biasanya mendapat blok IP sendiri. Misalnya kantor pelanggan dengan 20 perangkat diberi blok /27 dari jatah 203.0.113.0/24.\n\nTim NOC mencatat blok yang sudah terpakai di spreadsheet IPAM (IP Address Management) supaya tidak ada dua pelanggan memakai blok yang sama.",
                [[JenisMedia::Gambar, 'https://drive.google.com/file/d/1CONTOHtopologiPelangganx/view?usp=sharing', 'Contoh topologi jaringan pelanggan']],
            ],
            'media_video' => [
                'Tonton video berikut, lalu coba ulangi contoh perhitungannya di kertas.',
                [[JenisMedia::Youtube, 'https://www.youtube.com/watch?v=ecCuyq-Wprc', 'Penjelasan subnetting (bahasa Inggris, aktifkan subtitle)']],
            ],
            'studi_kasus' => ["Pelanggan baru, CV Maju Jaya, membutuhkan jaringan untuk 3 divisi: Keuangan (12 perangkat), Gudang (25 perangkat), dan Kantor Depan (6 perangkat). Jatah IP yang tersedia: 192.168.50.0/24.\n\nTentukan prefix yang paling hemat untuk setiap divisi, lalu tuliskan alamat network, rentang host, dan broadcast-nya."],
            'latihan' => ["Kerjakan tanpa kalkulator:\n1. Berapa host yang bisa dipakai pada /26?\n2. Apa alamat broadcast dari 10.10.10.64/27?\n3. Prefix terkecil yang cukup untuk 50 host adalah …?\n\nCocokkan jawaban Anda dengan tabel cepat di langkah Konsep Dasar."],
            'kuis' => ['Kerjakan kuis di bawah ini. Nilai minimal lulus adalah 75.'],
            'praktik_industri' => ["Minta izin pembimbing industri untuk melihat data IPAM (tanpa mengubahnya). Pilih satu blok pelanggan, lalu hitung sendiri rentang host dan broadcast-nya. Bandingkan hasil Anda dengan konfigurasi di router pelanggan.\n\nPenting: jangan mengubah konfigurasi perangkat produksi tanpa supervisi."],
            'refleksi' => ["Jawab di logbook Anda:\n- Bagian mana dari subnetting yang paling sulit bagi Anda?\n- Apa perbedaan cara menghitung di sekolah dengan cara yang dipakai tim NOC?\n- Apa yang akan Anda lakukan agar lebih cepat menghitung subnet?"],
        ];
    }

    /**
     * @return list<array{0: string, 1: list<string>, 2: int, 3: string}>
     */
    private function soalSubnetting(): array
    {
        return [
            ['Berapa jumlah host yang dapat dipakai pada prefix /27?', ['32', '30', '28', '14'], 1, '/27 = 2^5 = 32 alamat, dikurangi alamat network dan broadcast = 30 host.'],
            ['Subnet mask desimal untuk prefix /26 adalah …', ['255.255.255.0', '255.255.255.128', '255.255.255.192', '255.255.255.224'], 2, '/26 berarti 2 bit pertama oktet terakhir bernilai 1: 128 + 64 = 192.'],
            ['Alamat broadcast dari 192.168.1.32/28 adalah …', ['192.168.1.47', '192.168.1.48', '192.168.1.63', '192.168.1.39'], 0, '/28 berisi 16 alamat: 32 sampai 47. Alamat terakhir (47) adalah broadcast.'],
            ['Divisi dengan 25 perangkat paling hemat menggunakan prefix …', ['/28', '/27', '/26', '/25'], 1, '/28 hanya 14 host (kurang), /27 memberi 30 host (cukup dan paling hemat).'],
            ['Mengapa tim NOC mencatat blok IP di IPAM?', ['Agar internet lebih cepat', 'Agar tidak ada blok IP yang dipakai dua pelanggan', 'Agar router tidak perlu dikonfigurasi', 'Agar pelanggan bisa memilih IP sendiri'], 1, 'IPAM mencegah konflik alamat dan memudahkan pelacakan blok IP.'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1?: list<array{0: JenisMedia, 1: string, 2?: string}>}>
     */
    private function langkahGit(): array
    {
        return [
            'konsep_dasar' => [
                "Git mencatat riwayat perubahan kode dalam bentuk commit. Branch adalah jalur kerja terpisah agar fitur baru tidak mengganggu kode utama (main).\n\nAlur dasar:\n1. git checkout -b nama-branch\n2. Ubah kode, lalu git add dan git commit\n3. git push ke repository bersama\n4. Buat pull request agar perubahan diperiksa rekan tim",
                [[JenisMedia::Dokumen, 'https://docs.google.com/document/d/1CONTOHpanduanGitDasarxx/edit?usp=sharing', 'Ringkasan perintah Git dasar']],
            ],
            'contoh_industri' => [
                'Di software house, satu fitur = satu branch, misalnya feature/validasi-form-daftar. Setiap pull request wajib di-review minimal satu engineer sebelum digabung ke main. Pesan commit ditulis jelas, misalnya "Perbaiki validasi email di form daftar".',
                [[JenisMedia::Gambar, 'https://drive.google.com/file/d/1CONTOHalurPullRequestxx/view?usp=sharing', 'Diagram alur pull request di tim']],
            ],
            'media_video' => [
                'Tonton video singkat berikut tentang cara kerja Git.',
                [[JenisMedia::Youtube, 'https://youtu.be/e9lnsKot_SQ', 'Cara kerja Git dalam 4 menit (bahasa Inggris)']],
            ],
            'studi_kasus' => ["Dua siswa PKL mengubah file yang sama di branch berbeda. Saat pull request kedua akan digabung, muncul merge conflict.\n\nDiskusikan: apa penyebabnya, bagaimana cara menyelesaikannya, dan kebiasaan apa yang bisa mencegahnya?"],
            'latihan' => ["Di laptop Anda (bukan repository kantor):\n1. Buat repository baru dan satu file README.\n2. Buat branch latihan-1, ubah README, lalu commit.\n3. Kembali ke main, ubah baris yang sama, lalu commit.\n4. Gabungkan latihan-1 ke main dan selesaikan conflict yang muncul."],
            'kuis' => ['Kerjakan kuis di bawah ini. Nilai minimal lulus adalah 75.'],
            'praktik_industri' => ['Bersama pembimbing industri, buat satu pull request kecil di repository kantor (misalnya perbaikan teks). Perhatikan komentar code review dan perbaiki sesuai masukan.'],
            'refleksi' => ["Jawab di logbook Anda:\n- Apa perbedaan memakai Git sendiri di sekolah dengan memakai Git dalam tim?\n- Masukan code review apa yang paling berguna bagi Anda?"],
        ];
    }

    /**
     * @return list<array{0: string, 1: list<string>, 2: int, 3: string}>
     */
    private function soalGit(): array
    {
        return [
            ['Perintah untuk membuat branch baru sekaligus pindah ke branch tersebut adalah …', ['git branch -d fitur', 'git checkout -b fitur', 'git merge fitur', 'git push fitur'], 1, 'git checkout -b membuat branch baru dan langsung berpindah ke branch itu.'],
            ['Tujuan utama pull request adalah …', ['Menghapus branch lama', 'Meminta perubahan diperiksa sebelum digabung', 'Mengunduh kode dari server', 'Membuat repository baru'], 1, 'Pull request adalah tempat code review sebelum perubahan masuk ke main.'],
            ['Merge conflict terjadi ketika …', ['Internet terputus saat push', 'Dua branch mengubah baris yang sama', 'Commit tidak punya pesan', 'Branch terlalu banyak'], 1, 'Git tidak bisa memilih otomatis jika baris yang sama diubah berbeda di dua branch.'],
            ['Pesan commit yang paling baik adalah …', ['update', 'fix', 'Perbaiki validasi email di form daftar', 'asdf'], 2, 'Pesan commit harus menjelaskan apa yang diubah agar mudah dilacak tim.'],
            ['Perintah untuk mengirim commit lokal ke repository bersama adalah …', ['git pull', 'git push', 'git status', 'git log'], 1, 'git push mengirim commit dari komputer Anda ke remote repository.'],
        ];
    }
}
