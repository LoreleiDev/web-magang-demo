<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Kompetensi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Siswa mengganti kompetensi utama (dari Peta Kompetensi) dan unit kerja (dari
 * Dashboard) kapan saja — keputusan 13 no. 30 & 34. Progres tidak terpengaruh
 * karena progres disimpan per kompetensi.
 */
class PengaturanPendampinganController extends Controller
{
    public function kompetensiUtama(Request $request): RedirectResponse
    {
        $profil = $request->user()->profilSiswa;

        $data = $request->validate([
            'kompetensi_id' => [
                'required', 'integer',
                Rule::exists('kompetensi', 'id')->where('program_keahlian', $profil?->program_keahlian),
            ],
        ], ['kompetensi_id.exists' => 'Pilih kompetensi dari program keahlian Anda.']);

        $profil?->update(['kompetensi_fokus_id' => $data['kompetensi_id']]);

        $nama = Kompetensi::whereKey($data['kompetensi_id'])->value('nama_kompetensi_sekolah');
        Inertia::flash('toast', ['type' => 'success', 'message' => "Kompetensi utama Anda sekarang: {$nama}."]);

        return back();
    }

    public function unitKerja(Request $request): RedirectResponse
    {
        $profil = $request->user()->profilSiswa;

        $data = $request->validate([
            'unit_kerja' => ['required', 'string', Rule::in($profil?->kelompok?->perusahaan->daftar_unit_kerja ?? [])],
        ], ['unit_kerja.in' => 'Pilih unit kerja dari daftar perusahaan.']);

        $profil?->update(['unit_kerja' => $data['unit_kerja']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Unit kerja diganti ke {$data['unit_kerja']}."]);

        return back();
    }
}
