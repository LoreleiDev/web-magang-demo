<?php

namespace App\Http\Requests\Superadmin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Buat/edit akun guru, siswa, industri (CLAUDE.md bagian 5.1).
 * Role tidak bisa diubah setelah akun dibuat karena profil tiap role berbeda.
 */
class AkunRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $akun = $this->akun();
        $role = $this->role();
        $program = array_keys(config('magang.program_keahlian'));

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($akun)],
            'password' => [$akun ? 'nullable' : 'required', 'string', Password::min(8)],
            'role' => $akun ? ['prohibited'] : ['required', Rule::in([Role::Guru->value, Role::Siswa->value, Role::Industri->value])],

            // Wajib untuk siswa, opsional untuk guru.
            'program_keahlian' => [
                Rule::excludeIf(! in_array($role, [Role::Guru, Role::Siswa], true)),
                $role === Role::Siswa ? 'required' : 'nullable',
                Rule::in($program),
            ],

            // Guru
            'nip' => [Rule::excludeIf($role !== Role::Guru), 'nullable', 'string', 'max:30'],

            // Siswa
            'id_siswa' => [
                Rule::excludeIf($role !== Role::Siswa), 'required', 'string', 'max:30',
                Rule::unique('profil_siswa', 'id_siswa')->ignore($akun?->profilSiswa),
            ],

            // Industri
            'perusahaan_id' => [Rule::excludeIf($role !== Role::Industri), 'required', 'integer', Rule::exists('perusahaan', 'id')],
            'jabatan' => [Rule::excludeIf($role !== Role::Industri), 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'role' => 'jenis akun',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_siswa.unique' => 'ID siswa (NIS) ini sudah dipakai siswa lain.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
        ];
    }

    public function akun(): ?User
    {
        $akun = $this->route('akun');

        return $akun instanceof User ? $akun : null;
    }

    public function role(): ?Role
    {
        return $this->akun()->role ?? Role::tryFrom((string) $this->input('role'));
    }
}
