<?php

use App\Enums\Role;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

/*
| Impor akun dari Excel/CSV oleh superadmin (revisi 11 Okt 2026).
*/

beforeEach(function () {
    $this->admin = User::factory()->superadmin()->create();
});

function fileCsv(string $isi, string $nama = 'akun.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nama, $isi);
}

/**
 * @param  list<list<mixed>>  $baris
 */
function fileXlsx(array $baris): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'uji').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);
    foreach ($baris as $b) {
        $writer->addRow(Row::fromValues($b));
    }
    $writer->close();

    return new UploadedFile($path, 'akun.xlsx', null, null, true);
}

test('template per role bisa diunduh dan berisi judul kolom yang benar', function (string $role, array $kolom) {
    $respons = $this->actingAs($this->admin)->get(route('superadmin.akun.impor.template', $role));
    $respons->assertOk()->assertDownload("template-akun-{$role}.xlsx");

    $reader = new Reader;
    $reader->open($respons->getFile()->getPathname());
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            expect($row->toArray())->toBe($kolom);
            break 2;
        }
    }
    $reader->close();
})->with([
    'siswa' => ['siswa', ['nama', 'email', 'nis', 'program_keahlian']],
    'guru' => ['guru', ['nama', 'email', 'nip', 'program_keahlian']],
    'industri' => ['industri', ['nama', 'email', 'perusahaan', 'jabatan']],
]);

test('impor siswa dari Excel: password awal = NIS, NIS angka tidak menjadi desimal', function () {
    $file = fileXlsx([
        ['nama', 'email', 'nis', 'program_keahlian'],
        ['Andi Pratama', 'ANDI@sekolah.test', 232410001, 'tkj'],
        ['', '', '', ''],
        ['Bayu Saputra', 'bayu@sekolah.test', '232410002', 'RPL'],
    ]);

    $this->actingAs($this->admin)
        ->post(route('superadmin.akun.impor.store'), ['role' => 'siswa', 'file' => $file])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('hasilImpor.berhasil', true)
        ->assertInertiaFlash('hasilImpor.total', 2)
        ->assertInertiaFlash('hasilImpor.akun.0.password', '232410001')
        ->assertInertiaFlash('hasilImpor.akun.0.sumber_password', 'NIS');

    $andi = User::where('email', 'andi@sekolah.test')->sole();
    expect($andi->role)->toBe(Role::Siswa)
        ->and($andi->profilSiswa->id_siswa)->toBe('232410001')
        ->and($andi->profilSiswa->program_keahlian)->toBe('TKJ')
        ->and(Hash::check('232410001', $andi->password))->toBeTrue();

    $this->post(route('logout'));
    $this->post(route('login.store'), ['email' => 'andi@sekolah.test', 'password' => '232410001'])
        ->assertRedirect();
    $this->assertAuthenticatedAs($andi);
});

test('impor guru dari CSV Excel Indonesia (pemisah titik koma): NIP atau password acak', function () {
    $csv = "nama;email;nip;program_keahlian\nBudi Santoso;budi@sekolah.test;198703122010011004;TKJ\nSri Wahyuni;sri@sekolah.test;;\n";

    $this->actingAs($this->admin)
        ->post(route('superadmin.akun.impor.store'), ['role' => 'guru', 'file' => fileCsv($csv)])
        ->assertInertiaFlash('hasilImpor.akun.0.sumber_password', 'NIP')
        ->assertInertiaFlash('hasilImpor.akun.1.sumber_password', 'Acak');

    $sri = User::where('email', 'sri@sekolah.test')->sole();
    $passwordAcak = session('inertia.flash_data.hasilImpor.akun.1.password');

    expect($sri->profilGuru->nip)->toBeNull()
        ->and($sri->profilGuru->program_keahlian)->toBeNull()
        ->and(Hash::check('198703122010011004', User::where('email', 'budi@sekolah.test')->sole()->password))->toBeTrue()
        ->and(strlen((string) $passwordAcak))->toBe(10)
        ->and(Hash::check((string) $passwordAcak, $sri->password))->toBeTrue();
});

test('impor industri mencocokkan nama perusahaan tanpa beda huruf besar/kecil', function () {
    $perusahaan = Perusahaan::factory()->create(['nama' => 'PT Nusantara Jaringan Data']);
    $csv = "nama,email,perusahaan,jabatan\nHendra,hendra@pt.test,pt nusantara jaringan data,Supervisor\n";

    $this->actingAs($this->admin)
        ->post(route('superadmin.akun.impor.store'), ['role' => 'industri', 'file' => fileCsv($csv)])
        ->assertInertiaFlash('hasilImpor.akun.0.sumber_password', 'Acak');

    $hendra = User::where('email', 'hendra@pt.test')->sole();
    expect($hendra->role)->toBe(Role::Industri)
        ->and($hendra->profilIndustri->perusahaan_id)->toBe($perusahaan->id)
        ->and($hendra->profilIndustri->jabatan)->toBe('Supervisor');
});

test('baris yang salah dilaporkan per nomor baris dan tidak ada akun yang dibuat', function () {
    User::factory()->siswa(null, 'TKJ')->create(['email' => 'sudah@ada.test'])
        ->profilSiswa->update(['id_siswa' => '111']);

    $csv = implode("\n", [
        'nama,email,nis,program_keahlian',
        'Valid,valid@sekolah.test,500,TKJ',       // baris 2
        'Ganda,sudah@ada.test,501,TKJ',           // 3: email sudah ada
        ',kosong@sekolah.test,502,TKJ',           // 4: nama kosong
        'Program,program@sekolah.test,503,XYZ',   // 5: program tidak ada
        'Kembar,valid@sekolah.test,111,TKJ',      // 6: email kembar baris 2 + NIS sudah ada
    ]);

    $this->actingAs($this->admin)
        ->post(route('superadmin.akun.impor.store'), ['role' => 'siswa', 'file' => fileCsv($csv)])
        ->assertInertiaFlash('hasilImpor.berhasil', false)
        ->assertInertiaFlash('hasilImpor.total', 5)
        ->assertInertiaFlash('hasilImpor.error.0.baris', 3)
        ->assertInertiaFlash('hasilImpor.error.1.baris', 4)
        ->assertInertiaFlash('hasilImpor.error.2.baris', 5)
        ->assertInertiaFlash('hasilImpor.error.3.baris', 6)
        ->assertInertiaFlash('hasilImpor.error.3.pesan', [
            'Email valid@sekolah.test sama dengan baris 2.',
            'NIS 111 sudah dipakai siswa lain.',
        ]);

    expect(User::where('email', 'valid@sekolah.test')->exists())->toBeFalse();
});

test('industri dengan perusahaan yang belum terdaftar ditolak', function () {
    $csv = "nama,email,perusahaan,jabatan\nX,x@pt.test,PT Tidak Ada,\n";

    $this->actingAs($this->admin)
        ->post(route('superadmin.akun.impor.store'), ['role' => 'industri', 'file' => fileCsv($csv)])
        ->assertInertiaFlash('hasilImpor.error.0.pesan', ['Perusahaan "PT Tidak Ada" belum terdaftar. Tambahkan dulu di menu Perusahaan.']);

    expect(User::where('email', 'x@pt.test')->exists())->toBeFalse();
});

test('file yang tidak sesuai ditolak dengan pesan jelas', function (UploadedFile $file, string $pesan) {
    $this->actingAs($this->admin)
        ->post(route('superadmin.akun.impor.store'), ['role' => 'siswa', 'file' => $file])
        ->assertSessionHasErrors(['file' => $pesan]);
})->with([
    'file sql' => fn () => [fileCsv("INSERT INTO users VALUES (1);\n", 'akun.sql'), 'File harus berformat .xlsx atau .csv (bukan .xls atau .sql).'],
    'judul kolom salah' => fn () => [fileCsv("name,mail\nA,a@b.c\n"), 'Judul kolom tidak sesuai template. Kolom yang tidak ditemukan: nama, email, nis, program_keahlian. Gunakan template terbaru.'],
    'hanya judul' => fn () => [fileCsv("nama,email,nis,program_keahlian\n"), 'File tidak berisi data. Isi data mulai baris ke-2, di bawah judul kolom.'],
]);

test('hanya superadmin yang bisa mengunduh template dan mengimpor', function (string $role) {
    $user = User::factory()->{$role}()->create();
    $csv = "nama,email,nis,program_keahlian\nA,a@sekolah.test,1,TKJ\n";

    $this->actingAs($user)->get(route('superadmin.akun.impor.template', 'siswa'))->assertForbidden();
    $this->actingAs($user)
        ->post(route('superadmin.akun.impor.store'), ['role' => 'siswa', 'file' => fileCsv($csv)])
        ->assertForbidden();

    expect(User::where('email', 'a@sekolah.test')->exists())->toBeFalse();
})->with(['guru', 'industri', 'siswa']);
