<?php

namespace App\Models;

use App\Enums\JenisLangkah;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $materi_id
 * @property int $urutan
 * @property JenisLangkah $jenis
 * @property string|null $konten_teks
 */
#[Table('langkah_materi')]
#[Fillable(['materi_id', 'urutan', 'jenis', 'konten_teks'])]
class LangkahMateri extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'jenis' => JenisLangkah::class,
        ];
    }

    /**
     * @return BelongsTo<Materi, $this>
     */
    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    /**
     * @return HasMany<MediaMateri, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(MediaMateri::class)->orderBy('urutan');
    }
}
