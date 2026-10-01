<?php

namespace App\Http\Requests\Superadmin;

use App\Models\Perusahaan;
use App\Models\ProfilSiswa;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Data perusahaan (CLAUDE.md bagian 5.2). Dokumen industri boleh langsung
 * diunggah saat perusahaan didaftarkan.
 */
class PerusahaanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $dokumen = config('magang.dokumen_kb');

        return [
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'bidang_usaha' => ['nullable', 'string', 'max:255'],
            'daftar_unit_kerja' => ['required', 'array', 'min:1', 'max:30'],
            'daftar_unit_kerja.*' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'dokumen' => [$this->perusahaan() ? 'prohibited' : 'nullable', 'array', 'max:10'],
            'dokumen.*' => ['file', 'extensions:'.implode(',', $dokumen['ekstensi']), 'max:'.$dokumen['maks_kb']],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'daftar_unit_kerja.required' => 'Isi minimal satu unit kerja.',
            'daftar_unit_kerja.min' => 'Isi minimal satu unit kerja.',
            'daftar_unit_kerja.*.required' => 'Nama unit kerja tidak boleh kosong.',
            'daftar_unit_kerja.*.distinct' => 'Nama unit kerja tidak boleh sama.',
            'dokumen.*.extensions' => 'Dokumen harus berupa file PDF, DOCX, atau TXT.',
            'dokumen.*.max' => 'Ukuran setiap dokumen maksimal 20 MB.',
        ];
    }

    /**
     * Unit kerja yang masih dipilih siswa tidak boleh dihapus.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $perusahaan = $this->perusahaan();

            if ($perusahaan === null || $validator->errors()->isNotEmpty()) {
                return;
            }

            $baru = array_map('trim', (array) $this->input('daftar_unit_kerja'));
            $dihapus = array_diff($perusahaan->daftar_unit_kerja, $baru);

            $dipakai = ProfilSiswa::query()
                ->whereIn('unit_kerja', $dihapus)
                ->whereHas('kelompok', fn (Builder $q) => $q->where('perusahaan_id', $perusahaan->id))
                ->distinct()
                ->pluck('unit_kerja');

            if ($dipakai->isNotEmpty()) {
                $validator->errors()->add(
                    'daftar_unit_kerja',
                    'Unit kerja "'.$dipakai->implode('", "').'" masih dipilih siswa sehingga tidak bisa dihapus.',
                );
            }
        }];
    }

    /**
     * @return list<string>
     */
    public function unitKerja(): array
    {
        return array_values(array_map('trim', $this->validated('daftar_unit_kerja')));
    }

    public function perusahaan(): ?Perusahaan
    {
        $perusahaan = $this->route('perusahaan');

        return $perusahaan instanceof Perusahaan ? $perusahaan : null;
    }
}
