<?php

namespace App\Http\Requests\Superadmin;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Kelompok magang (CLAUDE.md bagian 5.3): satu perusahaan, satu guru pembimbing,
 * satu periode, dan daftar siswa.
 */
class KelompokMagangRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_kelompok' => ['required', 'string', 'max:255'],
            'perusahaan_id' => ['required', 'integer', Rule::exists('perusahaan', 'id')],
            'guru_pembimbing_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', Role::Guru->value)],
            'periode_mulai' => ['required', 'date'],
            'periode_selesai' => ['required', 'date', 'after_or_equal:periode_mulai'],
            'siswa_ids' => ['array'],
            'siswa_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('role', Role::Siswa->value)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guru_pembimbing_id.exists' => 'Pilih guru pembimbing dari daftar akun guru.',
            'periode_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'siswa_ids.*.exists' => 'Ada siswa yang tidak ditemukan.',
        ];
    }

    /**
     * @return list<int>
     */
    public function siswaIds(): array
    {
        return array_values(array_map('intval', $this->validated('siswa_ids', [])));
    }
}
