<?php

namespace App\Models;

use App\Enums\JenisDokumen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dokumen knowledge base AI Mentor (CLAUDE.md bagian 9.3).
 * Jenis sekolah: diunggah guru, dibatasi `program_keahlian`.
 * Jenis industri: diunggah superadmin, dibatasi `perusahaan_id`.
 *
 * @property int $id
 * @property JenisDokumen $jenis
 * @property string|null $program_keahlian
 * @property int|null $perusahaan_id
 * @property string $nama_file
 * @property string $path_file
 * @property int|null $diunggah_oleh
 * @property string|null $id_di_layanan_ai Nama dokumen di Gemini File Search.
 * @property string $status_ai menunggu | siap | gagal
 * @property string|null $pesan_error_ai
 */
#[Table('dokumen_knowledge_base')]
#[Fillable(['jenis', 'program_keahlian', 'perusahaan_id', 'nama_file', 'path_file', 'diunggah_oleh', 'id_di_layanan_ai', 'status_ai', 'pesan_error_ai'])]
class DokumenKnowledgeBase extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisDokumen::class,
        ];
    }

    /**
     * @return BelongsTo<Perusahaan, $this>
     */
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }
}
