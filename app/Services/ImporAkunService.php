<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\ImporAkunException;
use App\Models\Perusahaan;
use App\Models\ProfilSiswa;
use App\Models\ProgramKeahlian;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Impor akun guru/siswa/industri dari file Excel (.xlsx) atau CSV sesuai template
 * (revisi pemilik proyek, 11 Okt 2026).
 *
 * Password awal: siswa = NIS, guru = NIP (acak jika NIP kosong), industri = acak.
 * Semua baris divalidasi dulu; jika ada satu saja yang salah, tidak ada akun yang dibuat.
 *
 * @phpstan-type Kolom array{kunci: string, judul: string, wajib: bool, contoh: string, keterangan: string}
 * @phpstan-type HasilBaris array{nama: string, email: string, password: string, sumber_password: string}
 */
class ImporAkunService
{
    public const MAKS_BARIS = 500;

    /**
     * Kolom template per role. `kunci` adalah judul kolom di baris pertama file.
     *
     * @return list<Kolom>
     */
    public function kolom(Role $role): array
    {
        $dasar = [
            ['kunci' => 'nama', 'judul' => 'Nama lengkap', 'wajib' => true, 'contoh' => 'Andi Pratama', 'keterangan' => 'Nama yang tampil di website.'],
            ['kunci' => 'email', 'judul' => 'Email', 'wajib' => true, 'contoh' => 'andi@sekolah.sch.id', 'keterangan' => 'Dipakai untuk login. Harus unik.'],
        ];

        return match ($role) {
            Role::Siswa => [
                ...$dasar,
                ['kunci' => 'nis', 'judul' => 'NIS', 'wajib' => true, 'contoh' => '232410001', 'keterangan' => 'Harus unik. Juga menjadi password awal.'],
                ['kunci' => 'program_keahlian', 'judul' => 'Kode program keahlian', 'wajib' => true, 'contoh' => 'TKJ', 'keterangan' => 'Kode dari menu Program Keahlian.'],
            ],
            Role::Guru => [
                ...$dasar,
                ['kunci' => 'nip', 'judul' => 'NIP', 'wajib' => false, 'contoh' => '198703122010011004', 'keterangan' => 'Opsional. Menjadi password awal; jika kosong, password dibuat acak.'],
                ['kunci' => 'program_keahlian', 'judul' => 'Kode program keahlian', 'wajib' => false, 'contoh' => 'TKJ', 'keterangan' => 'Opsional. Kode dari menu Program Keahlian.'],
            ],
            Role::Industri => [
                ...$dasar,
                ['kunci' => 'perusahaan', 'judul' => 'Nama perusahaan', 'wajib' => true, 'contoh' => 'PT Nusantara Jaringan Data', 'keterangan' => 'Harus sama persis dengan nama di menu Perusahaan.'],
                ['kunci' => 'jabatan', 'judul' => 'Jabatan', 'wajib' => false, 'contoh' => 'Supervisor NOC', 'keterangan' => 'Opsional.'],
            ],
            Role::Superadmin => [],
        };
    }

    /**
     * Buat file template .xlsx: lembar "Data" (judul kolom) dan "Petunjuk".
     */
    public function template(Role $role, string $path): void
    {
        $kolom = $this->kolom($role);
        $judul = new Style(fontBold: true, fontColor: Color::WHITE, backgroundColor: '0B2545');

        $writer = new XlsxWriter;
        $writer->openToFile($path);

        $data = $writer->getCurrentSheet();
        $data->setName('Data');
        foreach (array_keys($kolom) as $i) {
            $data->setColumnWidth(32, $i + 1);
        }
        $writer->addRow(Row::fromValuesWithStyle(array_column($kolom, 'kunci'), $judul));

        $writer->addNewSheetAndMakeItCurrent()->setName('Petunjuk');
        $writer->getCurrentSheet()->setColumnWidth(28, 1, 2, 3);
        $writer->getCurrentSheet()->setColumnWidth(70, 4);
        $writer->addRow(Row::fromValuesWithStyle(['Kolom', 'Wajib?', 'Contoh', 'Keterangan'], $judul));
        foreach ($kolom as $k) {
            $writer->addRow(Row::fromValues([$k['kunci'], $k['wajib'] ? 'Wajib' : 'Opsional', $k['contoh'], $k['keterangan']]));
        }
        $writer->addRow(Row::fromValues([]));
        foreach ($this->catatan($role) as $baris) {
            $writer->addRow(Row::fromValues([$baris]));
        }

        $writer->close();
    }

    /**
     * Baca file menjadi daftar baris [nomor baris di file => [kolom => nilai]].
     *
     * @return array<int, array<string, string>>
     *
     * @throws ImporAkunException
     */
    public function baca(UploadedFile $file, Role $role): array
    {
        $ekstensi = strtolower($file->getClientOriginalExtension());
        $reader = $ekstensi === 'xlsx'
            ? new XlsxReader
            : new CsvReader(new CsvOptions(FIELD_DELIMITER: $this->pemisahCsv((string) $file->getRealPath())));

        try {
            $reader->open((string) $file->getRealPath());
        } catch (\Throwable) {
            throw new ImporAkunException('File tidak bisa dibaca. Pastikan formatnya .xlsx atau .csv dari template.');
        }

        $baris = [];
        $kunci = null;
        $wajib = array_column(array_filter($this->kolom($role), fn (array $k) => $k['wajib']), 'kunci');

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $nomor = 0;
                foreach ($sheet->getRowIterator() as $row) {
                    $nomor++;
                    $nilai = array_map(fn ($v) => $this->teks($v), $row->toArray());

                    if ($kunci === null) {
                        $kunci = array_map(fn (string $v) => Str::of($v)->lower()->trim()->replace(' ', '_')->toString(), $nilai);
                        $kurang = array_diff($wajib, $kunci);
                        if ($kurang !== []) {
                            throw new ImporAkunException('Judul kolom tidak sesuai template. Kolom yang tidak ditemukan: '.implode(', ', $kurang).'. Gunakan template terbaru.');
                        }

                        continue;
                    }

                    if (implode('', $nilai) === '') {
                        continue;
                    }

                    $isi = [];
                    foreach ($kunci as $i => $k) {
                        if ($k !== '') {
                            $isi[$k] = $nilai[$i] ?? '';
                        }
                    }
                    $baris[$nomor] = $isi;

                    if (count($baris) > self::MAKS_BARIS) {
                        throw new ImporAkunException('Maksimal '.self::MAKS_BARIS.' akun per file. Bagi data menjadi beberapa file.');
                    }
                }

                break; // hanya lembar pertama ("Data")
            }
        } finally {
            $reader->close();
        }

        if ($baris === []) {
            throw new ImporAkunException('File tidak berisi data. Isi data mulai baris ke-2, di bawah judul kolom.');
        }

        return $baris;
    }

    /**
     * Validasi semua baris sekaligus.
     *
     * @param  array<int, array<string, string>>  $baris
     * @return array<int, list<string>> pesan error per nomor baris (kosong = valid)
     */
    public function validasi(Role $role, array $baris): array
    {
        $program = array_change_key_case(ProgramKeahlian::opsi(), CASE_UPPER);
        $perusahaan = Perusahaan::pluck('id', 'nama')->mapWithKeys(fn ($id, $nama) => [mb_strtolower($nama) => $id]);
        $emailAda = User::whereIn('email', array_map(fn ($b) => mb_strtolower($b['email'] ?? ''), $baris))->pluck('email')->map(fn ($e) => mb_strtolower($e))->all();
        $nisAda = $role === Role::Siswa
            ? ProfilSiswa::whereIn('id_siswa', array_column($baris, 'nis'))->pluck('id_siswa')->all()
            : [];

        $error = [];
        $emailFile = [];
        $nisFile = [];

        foreach ($baris as $nomor => $b) {
            $pesan = Validator::make($b, [
                'nama' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'nis' => [$role === Role::Siswa ? 'required' : 'nullable', 'string', 'max:30'],
                'nip' => ['nullable', 'string', 'max:30'],
                'program_keahlian' => [$role === Role::Siswa ? 'required' : 'nullable', 'string'],
                'perusahaan' => [$role === Role::Industri ? 'required' : 'nullable', 'string'],
                'jabatan' => ['nullable', 'string', 'max:255'],
            ], [
                'required' => 'Kolom :attribute wajib diisi.',
                'email' => 'Format email tidak valid.',
                'max' => 'Kolom :attribute terlalu panjang.',
            ])->errors()->all();

            $email = mb_strtolower($b['email'] ?? '');
            if ($email !== '' && in_array($email, $emailAda, true)) {
                $pesan[] = "Email {$email} sudah dipakai akun lain.";
            }
            if ($email !== '' && isset($emailFile[$email])) {
                $pesan[] = "Email {$email} sama dengan baris {$emailFile[$email]}.";
            }
            $emailFile[$email] ??= $nomor;

            if ($role === Role::Siswa && ($b['nis'] ?? '') !== '') {
                if (in_array($b['nis'], $nisAda, true)) {
                    $pesan[] = "NIS {$b['nis']} sudah dipakai siswa lain.";
                }
                if (isset($nisFile[$b['nis']])) {
                    $pesan[] = "NIS {$b['nis']} sama dengan baris {$nisFile[$b['nis']]}.";
                }
                $nisFile[$b['nis']] ??= $nomor;
            }

            $kode = strtoupper($b['program_keahlian'] ?? '');
            if ($kode !== '' && ! isset($program[$kode])) {
                $pesan[] = "Kode program keahlian \"{$b['program_keahlian']}\" tidak ada. Pilihan: ".implode(', ', array_keys($program)).'.';
            }

            if ($role === Role::Industri && ($b['perusahaan'] ?? '') !== '' && ! $perusahaan->has(mb_strtolower($b['perusahaan']))) {
                $pesan[] = "Perusahaan \"{$b['perusahaan']}\" belum terdaftar. Tambahkan dulu di menu Perusahaan.";
            }

            if ($pesan !== []) {
                $error[$nomor] = array_values(array_unique($pesan));
            }
        }

        return $error;
    }

    /**
     * Buat semua akun dalam satu transaksi. Panggil setelah validasi() kosong.
     *
     * @param  array<int, array<string, string>>  $baris
     * @return list<HasilBaris>
     */
    public function impor(Role $role, array $baris): array
    {
        $perusahaan = Perusahaan::pluck('id', 'nama')->mapWithKeys(fn ($id, $nama) => [mb_strtolower($nama) => $id]);

        return DB::transaction(function () use ($role, $baris, $perusahaan) {
            $hasil = [];

            foreach ($baris as $b) {
                [$password, $sumber] = match (true) {
                    $role === Role::Siswa => [$b['nis'], 'NIS'],
                    $role === Role::Guru && ($b['nip'] ?? '') !== '' => [$b['nip'], 'NIP'],
                    default => [Str::password(10, symbols: false), 'Acak'],
                };

                $user = User::create([
                    'name' => trim($b['nama']),
                    'email' => mb_strtolower(trim($b['email'])),
                    'password' => $password,
                    'role' => $role,
                    'status_aktif' => true,
                ]);

                $program = ($b['program_keahlian'] ?? '') === '' ? null : strtoupper($b['program_keahlian']);

                match ($role) {
                    Role::Siswa => $user->profilSiswa()->create(['id_siswa' => $b['nis'], 'program_keahlian' => $program]),
                    Role::Guru => $user->profilGuru()->create(['nip' => ($b['nip'] ?? '') ?: null, 'program_keahlian' => $program]),
                    Role::Industri => $user->profilIndustri()->create([
                        'perusahaan_id' => $perusahaan[mb_strtolower($b['perusahaan'])],
                        'jabatan' => ($b['jabatan'] ?? '') ?: null,
                    ]),
                    Role::Superadmin => null,
                };

                $hasil[] = ['nama' => $user->name, 'email' => $user->email, 'password' => $password, 'sumber_password' => $sumber];
            }

            return $hasil;
        });
    }

    /**
     * @return list<string>
     */
    private function catatan(Role $role): array
    {
        $catatan = [
            'Cara mengisi:',
            '1. Isi data di lembar "Data", mulai baris ke-2. Jangan ubah judul kolom di baris pertama.',
            '2. Agar angka nol di depan (NIS/NIP) tidak hilang, ubah format kolom menjadi Text sebelum mengisi.',
            '3. Satu file maksimal '.self::MAKS_BARIS.' akun. Jika ada baris yang salah, tidak ada akun yang dibuat sampai semua diperbaiki.',
        ];

        $catatan[] = match ($role) {
            Role::Siswa => '4. Password awal siswa = NIS. Program keahlian yang tersedia: '.implode(', ', array_keys(ProgramKeahlian::opsi())).'.',
            Role::Guru => '4. Password awal guru = NIP; jika NIP kosong, password dibuat acak dan ditampilkan setelah impor.',
            Role::Industri => '4. Password awal industri dibuat acak dan ditampilkan setelah impor. Perusahaan yang tersedia: '
                .Perusahaan::orderBy('nama')->pluck('nama')->implode('; ').'.',
            Role::Superadmin => '',
        };

        return $catatan;
    }

    /**
     * Nilai sel menjadi teks. Angka bulat dari Excel (mis. NIS 232410001.0) tanpa ".0".
     */
    private function teks(mixed $nilai): string
    {
        return trim(match (true) {
            $nilai instanceof \DateTimeInterface => $nilai->format('Y-m-d'),
            is_float($nilai) && floor($nilai) === $nilai && abs($nilai) < 1e15 => number_format($nilai, 0, '', ''),
            is_scalar($nilai) => (string) $nilai,
            default => '',
        });
    }

    /**
     * Excel versi Indonesia menyimpan CSV dengan ";" sebagai pemisah.
     */
    private function pemisahCsv(string $path): string
    {
        $file = fopen($path, 'r') ?: throw new ImporAkunException('File tidak bisa dibuka.');
        $baris = (string) fgets($file);
        fclose($file);

        return substr_count($baris, ';') > substr_count($baris, ',') ? ';' : ',';
    }
}
