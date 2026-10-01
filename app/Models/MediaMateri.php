<?php

namespace App\Models;

use App\Enums\JenisMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Media materi berupa link. `id_media` adalah ID video YouTube atau ID file
 * Google Drive yang diambil dari link saat disimpan (CLAUDE.md bagian 6.4.1).
 *
 * @property int $id
 * @property int $langkah_materi_id
 * @property int $urutan
 * @property JenisMedia $jenis
 * @property string $url
 * @property string $id_media
 * @property string|null $keterangan
 */
#[Table('media_materi')]
#[Fillable(['langkah_materi_id', 'urutan', 'jenis', 'url', 'id_media', 'keterangan'])]
class MediaMateri extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'jenis' => JenisMedia::class,
        ];
    }

    /**
     * @return BelongsTo<LangkahMateri, $this>
     */
    public function langkah(): BelongsTo
    {
        return $this->belongsTo(LangkahMateri::class, 'langkah_materi_id');
    }
}
