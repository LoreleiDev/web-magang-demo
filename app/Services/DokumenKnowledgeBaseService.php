<?php

namespace App\Services;

use App\Enums\JenisDokumen;
use App\Models\DokumenKnowledgeBase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Menyimpan & menghapus dokumen knowledge base AI Mentor (CLAUDE.md bagian 9.3).
 *
 * Tahap 2-3: file hanya disimpan di server (disk privat "local").
 * Tahap 6: dokumen juga diteruskan ke Gemini dan id-nya disimpan di `id_di_layanan_ai`.
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

        return DokumenKnowledgeBase::create([
            'jenis' => $jenis,
            ...$cakupan,
            'nama_file' => $file->getClientOriginalName(),
            'path_file' => $path,
            'diunggah_oleh' => $pengunggah->id,
        ]);
    }
}
