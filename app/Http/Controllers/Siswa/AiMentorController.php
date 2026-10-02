<?php

namespace App\Http\Controllers\Siswa;

use App\Exceptions\AiMentorException;
use App\Http\Controllers\Controller;
use App\Models\ChatAiMentor;
use App\Models\Kompetensi;
use App\Services\AiMentorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Endpoint JSON floating chatbot AI Mentor (CLAUDE.md bagian 6.5 & 9).
 * React -> route ini -> AiMentorService -> Gemini. API key tidak pernah ke frontend.
 */
class AiMentorController extends Controller
{
    /** Pesan yang disimpan per siswa (riwayat lama dipangkas). */
    private const MAKS_PESAN = 40;

    public function riwayat(Request $request): JsonResponse
    {
        Gate::authorize('gunakan-ai-mentor');

        return response()->json([
            'pesan' => $this->percakapan($request)->pesan ?? [],
        ]);
    }

    public function kirim(Request $request, AiMentorService $ai): JsonResponse
    {
        Gate::authorize('gunakan-ai-mentor');

        $siswa = $request->user();
        $data = $request->validate([
            'pesan' => ['required', 'string', 'max:2000'],
            'kompetensi_id' => [
                'nullable', 'integer',
                Rule::exists('kompetensi', 'id')->where('program_keahlian', $siswa->programKeahlian()),
            ],
        ], ['pesan.required' => 'Tulis pesan terlebih dahulu.']);

        $percakapan = $this->percakapan($request) ?? new ChatAiMentor(['siswa_id' => $siswa->id, 'pesan' => []]);
        $riwayat = $percakapan->pesan;

        try {
            $jawaban = $ai->jawab(
                $siswa,
                array_map(fn (array $p) => ['peran' => $p['peran'], 'isi' => $p['isi']], $riwayat),
                $data['pesan'],
                isset($data['kompetensi_id']) ? Kompetensi::whereKey($data['kompetensi_id'])->first() : null,
            );
        } catch (AiMentorException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        $balasan = ['peran' => 'mentor', 'isi' => $jawaban['teks'], 'sumber' => $jawaban['sumber']];

        $percakapan->pesan = array_slice([
            ...$riwayat,
            ['peran' => 'siswa', 'isi' => $data['pesan']],
            $balasan,
        ], -self::MAKS_PESAN);
        $percakapan->save();

        return response()->json(['balasan' => $balasan]);
    }

    /**
     * Mulai percakapan baru.
     */
    public function hapus(Request $request): JsonResponse
    {
        Gate::authorize('gunakan-ai-mentor');

        ChatAiMentor::where('siswa_id', $request->user()->id)->delete();

        return response()->json(['pesan' => []]);
    }

    private function percakapan(Request $request): ?ChatAiMentor
    {
        return ChatAiMentor::where('siswa_id', $request->user()->id)->latest('id')->first();
    }
}
