<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Unggah satu atau beberapa dokumen knowledge base (sekolah/industri).
 */
class UnggahDokumenRequest extends FormRequest
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
            'dokumen' => ['required', 'array', 'min:1', 'max:10'],
            'dokumen.*' => ['file', 'extensions:'.implode(',', $dokumen['ekstensi']), 'max:'.$dokumen['maks_kb']],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dokumen.required' => 'Pilih minimal satu dokumen.',
            'dokumen.*.extensions' => 'Dokumen harus berupa file PDF, DOCX, atau TXT.',
            'dokumen.*.max' => 'Ukuran setiap dokumen maksimal 20 MB.',
        ];
    }
}
