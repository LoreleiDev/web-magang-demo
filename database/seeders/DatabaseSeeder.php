<?php

namespace Database\Seeders;

use App\Enums\HasilVerifikasiMateri;
use App\Enums\Role;
use App\Enums\StatusKompetensi;
use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Logbook;
use App\Models\Materi;
use App\Models\Perusahaan;
use App\Models\ProfilGuru;
use App\Models\ProfilIndustri;
use App\Models\ProfilSiswa;
use App\Models\ProgramKeahlian;
use App\Models\ProgresKompetensi;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data contoh pengembangan. Semua akun memakai kata sandi "password".
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const PASSWORD = 'password';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            'TKJ' => 'Teknik Komputer dan Jaringan',
            'RPL' => 'Rekayasa Perangkat Lunak',
            'TKR' => 'Teknik Kendaraan Ringan',
            'AKL' => 'Akuntansi dan Keuangan Lembaga',
        ] as $kode => $nama) {
            ProgramKeahlian::updateOrCreate(['kode' => $kode], ['nama' => $nama]);
        }

        $this->akun('Admin Sekolah', 'admin@magangbridge.test', Role::Superadmin);

        $guruTkj = $this->guru('Budi Santoso, S.Kom.', 'budi.santoso@magangbridge.test', '198703122010011004', 'TKJ');
        $guruRpl = $this->guru('Sri Wahyuni, S.Pd.', 'sri.wahyuni@magangbridge.test', '199002182015032007', 'RPL');

        $nusantara = Perusahaan::create([
            'nama' => 'PT Nusantara Jaringan Data',
            'alamat' => 'Jl. Gatot Subroto No. 45, Bandung',
            'bidang_usaha' => 'Penyedia layanan internet (ISP)',
            'daftar_unit_kerja' => ['Network Operation Center (NOC)', 'Instalasi Jaringan', 'Helpdesk IT'],
        ]);
        $solusi = Perusahaan::create([
            'nama' => 'CV Solusi Digital Kreatif',
            'alamat' => 'Jl. Dago No. 112, Bandung',
            'bidang_usaha' => 'Pengembangan perangkat lunak',
            'daftar_unit_kerja' => ['Backend', 'Frontend', 'Quality Assurance'],
        ]);

        $industriNusantara = $this->industri('Hendra Gunawan', 'hendra@nusantaranet.test', $nusantara, 'Supervisor NOC');
        $industriSolusi = $this->industri('Maya Kartika', 'maya@solusidigital.test', $solusi, 'Lead Engineer');

        $mulai = Carbon::parse('2026-09-14');
        $kelompokTkj = KelompokMagang::create([
            'nama_kelompok' => 'PKL TKJ 2026 - Nusantara Jaringan Data',
            'perusahaan_id' => $nusantara->id,
            'guru_pembimbing_id' => $guruTkj->id,
            'periode_mulai' => $mulai,
            'periode_selesai' => $mulai->copy()->addWeeks(13),
        ]);
        $kelompokRpl = KelompokMagang::create([
            'nama_kelompok' => 'PKL RPL 2026 - Solusi Digital Kreatif',
            'perusahaan_id' => $solusi->id,
            'guru_pembimbing_id' => $guruRpl->id,
            'periode_mulai' => $mulai,
            'periode_selesai' => $mulai->copy()->addWeeks(13),
        ]);

        $andi = $this->siswa('Andi Pratama', 'andi@siswa.test', '232410001', 'TKJ', $kelompokTkj, 'Network Operation Center (NOC)');
        $bayu = $this->siswa('Bayu Saputra', 'bayu@siswa.test', '232410002', 'TKJ', $kelompokTkj, 'Instalasi Jaringan');
        $citra = $this->siswa('Citra Lestari', 'citra@siswa.test', '232410003', 'TKJ', $kelompokTkj, null);
        $dewi = $this->siswa('Dewi Anggraini', 'dewi@siswa.test', '232420001', 'RPL', $kelompokRpl, 'Backend');
        $eko = $this->siswa('Eko Nugroho', 'eko@siswa.test', '232420002', 'RPL', $kelompokRpl, 'Frontend');
        $fajar = $this->siswa('Fajar Ramadhan', 'fajar@siswa.test', '232420003', 'RPL', $kelompokRpl, null);

        $kompetensiTkj = $this->kompetensi($guruTkj, 'TKJ', [
            ['Konfigurasi jaringan LAN', 'Memasang dan mengonfigurasi router serta switch di jaringan pelanggan', 3],
            ['Pengalamatan IP dan subnetting', 'Merencanakan pembagian alamat IP untuk pelanggan baru', 3],
            ['Troubleshooting jaringan', 'Menangani tiket gangguan pelanggan di NOC sesuai SLA', 4],
            ['K3 instalasi jaringan', 'Memasang kabel fiber optik di lapangan sesuai SOP K3 perusahaan', 4],
        ]);
        $kompetensiRpl = $this->kompetensi($guruRpl, 'RPL', [
            ['Pemrograman web dasar', 'Membuat fitur baru pada aplikasi web milik klien', 3],
            ['Basis data', 'Merancang tabel dan menulis query untuk laporan klien', 3],
            ['Version control dengan Git', 'Bekerja dalam tim lewat branch, pull request, dan code review', 3],
            ['Pengujian perangkat lunak', 'Menulis test case dan melaporkan bug di issue tracker', 4],
        ]);

        // [level_siswa, status] per kompetensi (urutan sama dengan daftar di atas). Level > 1
        // berarti siswa sudah lulus kuis materi berlevel tsb sebelumnya (bagian 11.1).
        $this->progres($andi, $kompetensiTkj, $guruTkj, $industriNusantara, [
            [3, StatusKompetensi::Terverifikasi], [1, StatusKompetensi::SedangDipelajari],
            [3, StatusKompetensi::SedangDipraktikkan], [2, StatusKompetensi::SedangDipraktikkan],
        ]);
        $this->progres($bayu, $kompetensiTkj, $guruTkj, $industriNusantara, [
            [2, StatusKompetensi::SedangDipraktikkan], [3, StatusKompetensi::MenungguVerifikasi],
            [2, StatusKompetensi::SedangDipraktikkan], [3, StatusKompetensi::SedangDipraktikkan],
        ]);
        $this->progres($citra, $kompetensiTkj, $guruTkj, $industriNusantara, array_fill(0, 4, [1, StatusKompetensi::BelumDipelajari]));
        $this->progres($dewi, $kompetensiRpl, $guruRpl, $industriSolusi, [
            [3, StatusKompetensi::MenungguVerifikasi], [2, StatusKompetensi::SedangDipraktikkan],
            [3, StatusKompetensi::MenungguVerifikasi], [2, StatusKompetensi::SedangDipraktikkan],
        ]);
        $this->progres($eko, $kompetensiRpl, $guruRpl, $industriSolusi, [
            [2, StatusKompetensi::SedangDipraktikkan], [3, StatusKompetensi::Terverifikasi],
            [1, StatusKompetensi::SedangDipelajari], [3, StatusKompetensi::SedangDipraktikkan],
        ]);
        $this->progres($fajar, $kompetensiRpl, $guruRpl, $industriSolusi, array_fill(0, 4, [1, StatusKompetensi::BelumDipelajari]));

        $this->call(MateriSeeder::class, parameters: [
            'kompetensiSubnetting' => $kompetensiTkj[1],
            'kompetensiGit' => $kompetensiRpl[2],
            'percobaan' => [
                // [siswa, materi ('tkj'|'rpl'), skor]
                [$andi, 'tkj', 60],
                [$bayu, 'tkj', 80],
                [$dewi, 'rpl', 90],
            ],
        ]);

        $this->logbookContoh($andi, $dewi);

        // Contoh verifikasi materi: industri TKJ memverifikasi materi subnetting (badge tampil).
        $materiTkj = Materi::where('kompetensi_id', $kompetensiTkj[1]->id)->firstOrFail();
        $materiTkj->verifikasi()->create([
            'diperiksa_oleh' => $industriNusantara->id,
            'perusahaan_id' => $nusantara->id,
            'hasil' => HasilVerifikasiMateri::Diverifikasi,
            'masukan' => 'Sudah sesuai dengan praktik IPAM di NOC kami. Bisa ditambah contoh VLSM.',
            'email_terkirim' => true,
        ]);
        $materiTkj->update(['terverifikasi_industri' => true]);
    }

    private function akun(string $nama, string $email, Role $role): User
    {
        return User::create([
            'name' => $nama,
            'email' => $email,
            'password' => self::PASSWORD,
            'role' => $role,
            'status_aktif' => true,
        ]);
    }

    private function guru(string $nama, string $email, string $nip, string $program): User
    {
        $user = $this->akun($nama, $email, Role::Guru);
        ProfilGuru::create(['user_id' => $user->id, 'nip' => $nip, 'program_keahlian' => $program]);

        return $user;
    }

    private function industri(string $nama, string $email, Perusahaan $perusahaan, string $jabatan): User
    {
        $user = $this->akun($nama, $email, Role::Industri);
        ProfilIndustri::create(['user_id' => $user->id, 'perusahaan_id' => $perusahaan->id, 'jabatan' => $jabatan]);

        return $user;
    }

    private function siswa(string $nama, string $email, string $nis, string $program, KelompokMagang $kelompok, ?string $unit): User
    {
        $user = $this->akun($nama, $email, Role::Siswa);
        ProfilSiswa::create([
            'user_id' => $user->id,
            'id_siswa' => $nis,
            'program_keahlian' => $program,
            'unit_kerja' => $unit,
            'kelompok_id' => $kelompok->id,
        ]);

        return $user;
    }

    /**
     * @param  list<array{0: string, 1: string, 2: int}>  $daftar
     * @return list<Kompetensi>
     */
    private function kompetensi(User $guru, string $program, array $daftar): array
    {
        return array_map(fn (array $k) => Kompetensi::create([
            'program_keahlian' => $program,
            'nama_kompetensi_sekolah' => $k[0],
            'aktivitas_kompetensi_industri' => $k[1],
            'target_level' => $k[2],
            'dibuat_oleh' => $guru->id,
        ]), $daftar);
    }

    /**
     * @param  list<Kompetensi>  $kompetensi
     * @param  list<array{0: int, 1: StatusKompetensi}>  $data
     */
    private function progres(User $siswa, array $kompetensi, User $guru, User $industri, array $data): void
    {
        foreach ($kompetensi as $i => $k) {
            [$level, $status] = $data[$i];
            $terverifikasi = $status === StatusKompetensi::Terverifikasi;

            ProgresKompetensi::create([
                'siswa_id' => $siswa->id,
                'kompetensi_id' => $k->id,
                'level_siswa' => $level,
                'level_diisi_oleh' => null,
                'tanggal_level_diisi' => Carbon::parse('2026-09-25 10:00'),
                'status' => $status,
                'diverifikasi_oleh' => $terverifikasi ? $industri->id : null,
                'tanggal_verifikasi' => $terverifikasi ? Carbon::parse('2026-09-29 14:30') : null,
            ]);
        }
    }

    private function logbookContoh(User $andi, User $dewi): void
    {
        Logbook::create([
            'siswa_id' => $andi->id,
            'tanggal' => '2026-09-30',
            'aktivitas' => 'Memantau dashboard NOC dan ikut menangani tiket gangguan pelanggan yang koneksinya putus.',
            'peralatan_software' => 'Winbox, PRTG Network Monitor, aplikasi tiket internal',
            'sudah_dipahami' => 'Cara membaca status interface di Winbox.',
            'baru_ditemui' => 'Monitoring trafik pelanggan dengan PRTG.',
            'kesulitan' => 'Menentukan subnet pelanggan dari prefix /27 dengan cepat.',
            'pengetahuan_sekolah_digunakan' => 'Konfigurasi IP address dan perintah ping/traceroute.',
            'ingin_dipelajari' => 'Subnetting yang lebih cepat dan cara membaca grafik PRTG.',
        ]);

        Logbook::create([
            'siswa_id' => $dewi->id,
            'tanggal' => '2026-09-30',
            'aktivitas' => 'Memperbaiki bug validasi form pendaftaran dan membuat pull request pertama.',
            'peralatan_software' => 'VS Code, Git, GitHub, Laravel',
            'sudah_dipahami' => 'Membuat branch baru dan commit perubahan.',
            'baru_ditemui' => 'Proses code review dan aturan penamaan branch di kantor.',
            'kesulitan' => 'Menyelesaikan merge conflict setelah branch main berubah.',
            'pengetahuan_sekolah_digunakan' => 'Validasi form dengan PHP.',
            'ingin_dipelajari' => 'Cara rebase dan menyelesaikan conflict dengan benar.',
        ]);
    }
}
