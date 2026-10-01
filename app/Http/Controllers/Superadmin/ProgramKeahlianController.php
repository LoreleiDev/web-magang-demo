<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ProgramKeahlian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar program keahlian dikelola superadmin (keputusan 13 no. 15).
 * Kode tidak bisa diubah karena dipakai data lain; program yang dipakai tidak bisa dihapus.
 */
class ProgramKeahlianController extends Controller
{
    public function index(): Response
    {
        $hitung = fn (string $tabel) => DB::table($tabel)
            ->whereNotNull('program_keahlian')
            ->selectRaw('program_keahlian, count(*) as total')
            ->groupBy('program_keahlian')
            ->pluck('total', 'program_keahlian');

        $siswa = $hitung('profil_siswa');
        $guru = $hitung('profil_guru');
        $kompetensi = $hitung('kompetensi');

        return Inertia::render('superadmin/program-keahlian', [
            'program' => ProgramKeahlian::orderBy('kode')->get()->map(fn (ProgramKeahlian $p) => [
                'id' => $p->id,
                'kode' => $p->kode,
                'nama' => $p->nama,
                'jumlah_siswa' => (int) ($siswa[$p->kode] ?? 0),
                'jumlah_guru' => (int) ($guru[$p->kode] ?? 0),
                'jumlah_kompetensi' => (int) ($kompetensi[$p->kode] ?? 0),
                'bisa_dihapus' => ! $p->sedangDipakai(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('program_keahlian', 'kode')],
            'nama' => ['required', 'string', 'max:255'],
        ], [
            'kode.regex' => 'Kode hanya boleh berisi huruf, angka, dan tanda hubung.',
            'kode.unique' => 'Kode ini sudah dipakai program lain.',
        ]);

        ProgramKeahlian::create(['kode' => strtoupper($data['kode']), 'nama' => $data['nama']]);

        return $this->selesai('Program keahlian ditambahkan.');
    }

    public function update(Request $request, ProgramKeahlian $programKeahlian): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['prohibited'],
            'nama' => ['required', 'string', 'max:255'],
        ], ['kode.prohibited' => 'Kode program tidak bisa diubah.']);

        $programKeahlian->update(['nama' => $data['nama']]);

        return $this->selesai('Nama program keahlian diperbarui.');
    }

    public function destroy(ProgramKeahlian $programKeahlian): RedirectResponse
    {
        if ($programKeahlian->sedangDipakai()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Program ini masih dipakai siswa, guru, kompetensi, atau dokumen, jadi belum bisa dihapus.',
            ]);

            return back();
        }

        $programKeahlian->delete();

        return $this->selesai("Program {$programKeahlian->kode} dihapus.");
    }

    private function selesai(string $pesan): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $pesan]);

        return to_route('superadmin.program-keahlian.index');
    }
}
