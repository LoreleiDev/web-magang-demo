<?php

namespace App\Services;

use App\Enums\JenisDokumen;
use App\Jobs\HapusDokumenAi;
use App\Jobs\SinkronDokumenKnowledgeBase;
use App\Models\DokumenKnowledgeBase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Menyimpan & menghapus dokumen knowledge base AI Mentor (CLAUDE.md bagian 9.3).
 *
 * File disimpan di disk privat "local", lalu diteruskan ke Gemini File Search lewat
 * queue (SinkronDokumenKnowledgeBase); nama dokumennya disimpan di `id_di_layanan_ai`.
 */
class DokumenKnowledgeBaseService
{
    private const DISK = 'local';

    public function simpanIndustri(UploadedFile $file, int $perusahaanId, User $pengunggah): DokumenKnowledgeBase
    {
        return $this->simpan($file, JenisDokumen::Industri, "knowledge-base/industri/{$perusahaanId}", $pengunggah, [
            'perusahaan_id' => $perusahaanId,
        ]);
    }

    public function simpanSekolah(UploadedFile $file, string $programKeahlian, User $pengunggah): DokumenKnowledgeBase
    {
        return $this->simpan($file, JenisDokumen::Sekolah, "knowledge-base/sekolah/{$programKeahlian}", $pengunggah, [
            'program_keahlian' => $programKeahlian,
        ]);
    }

    public function hapus(DokumenKnowledgeBase $dokumen): void
    {
        if ($dokumen->id_di_layanan_ai !== null) {
            HapusDokumenAi::dispatch($dokumen->id_di_layanan_ai)->afterCommit();
        }

        Storage::disk(self::DISK)->delete($dokumen->path_file);

        $dokumen->delete();
    }

    public function path(DokumenKnowledgeBase $dokumen): string
    {
        return Storage::disk(self::DISK)->path($dokumen->path_file);
    }

    /**
     * @param  array<string, mixed>  $cakupan
     */
    private function simpan(UploadedFile $file, JenisDokumen $jenis, string $folder, User $pengunggah, array $cakupan): DokumenKnowledgeBase
    {
        $path = $file->store($folder, self::DISK);

        $dokumen = DokumenKnowledgeBase::create([
            'jenis' => $jenis,
            ...$cakupan,
            'nama_file' => $file->getClientOriginalName(),
            'path_file' => $path,
            'diunggah_oleh' => $pengunggah->id,
            'status_ai' => 'menunggu',
        ]);

        // Diteruskan ke Gemini File Search lewat queue (bagian 9.3).
        SinkronDokumenKnowledgeBase::dispatch($dokumen)->afterCommit();

        return $dokumen;
    }
}
