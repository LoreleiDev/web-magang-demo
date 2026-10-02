<?php

namespace App\Http\Requests\Guru;

use App\Enums\JenisMedia;
use App\Support\MediaLink;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Materi 8 langkah + media link + kuis (CLAUDE.md bagian 6.4, 6.4.1, 7).
 */
class MateriRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kompetensi_id' => [
                'required', 'integer',
                Rule::exists('kompetensi', 'id')->where('program_keahlian', $this->user()?->programKeahlian()),
            ],
            'judul' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'between:1,4'],
            'nilai_minimal' => ['required', 'integer', 'between:1,100'],

            'langkah' => ['required', 'array', 'size:8'],
            'langkah.*.konten_teks' => ['nullable', 'string', 'max:20000'],
            'langkah.*.media' => ['array', 'max:5'],
            'langkah.*.media.*.jenis' => ['required', Rule::enum(JenisMedia::class)],
            'langkah.*.media.*.url' => ['required', 'string', 'max:500'],
            'langkah.*.media.*.keterangan' => ['nullable', 'string', 'max:255'],

            'soal' => ['required', 'array', 'min:1', 'max:30'],
            'soal.*.pertanyaan' => ['required', 'string', 'max:2000'],
            'soal.*.pilihan' => ['required', 'array', 'min:2', 'max:5'],
            'soal.*.pilihan.*' => ['required', 'string', 'max:500'],
            'soal.*.jawaban_benar' => ['required', 'integer', 'min:0'],
            'soal.*.pembahasan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kompetensi_id.required' => 'Pilih kompetensi untuk materi ini.',
            'kompetensi_id.exists' => 'Kompetensi harus dari program keahlian Anda.',
            'langkah.size' => 'Materi harus berisi 8 langkah.',
            'level.between' => 'Level materi harus 1 sampai 4.',
            'nilai_minimal.between' => 'Nilai minimal harus 1 sampai 100.',
            'langkah.*.media.max' => 'Maksimal 5 media per langkah.',
            'langkah.*.media.*.url.required' => 'Link media wajib diisi.',
            'soal.required' => 'Tambahkan minimal satu soal kuis.',
            'soal.min' => 'Tambahkan minimal satu soal kuis.',
            'soal.*.pertanyaan.required' => 'Pertanyaan wajib diisi.',
            'soal.*.pilihan.min' => 'Minimal 2 pilihan jawaban.',
            'soal.*.pilihan.max' => 'Maksimal 5 pilihan jawaban.',
            'soal.*.pilihan.*.required' => 'Pilihan jawaban tidak boleh kosong.',
            'soal.*.jawaban_benar.required' => 'Tandai jawaban yang benar.',
        ];
    }

    /**
     * Link media harus sesuai jenisnya (YouTube / Google Drive), dan jawaban benar
     * harus menunjuk salah satu pilihan.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ((array) $this->input('langkah') as $i => $langkah) {
                foreach ((array) ($langkah['media'] ?? []) as $j => $media) {
                    $jenis = JenisMedia::tryFrom((string) ($media['jenis'] ?? ''));
                    $url = (string) ($media['url'] ?? '');

                    if ($jenis !== null && $url !== '' && MediaLink::ambilId($jenis, $url) === null) {
                        $validator->errors()->add("langkah.{$i}.media.{$j}.url", MediaLink::pesanError($jenis));
                    }
                }
            }

            foreach ((array) $this->input('soal') as $i => $soal) {
                $jawaban = $soal['jawaban_benar'] ?? null;

                if (is_numeric($jawaban) && (int) $jawaban >= count((array) ($soal['pilihan'] ?? []))) {
                    $validator->errors()->add("soal.{$i}.jawaban_benar", 'Tandai jawaban yang benar.');
                }
            }
        }];
    }
}
