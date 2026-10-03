<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MateriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Materi umum per program keahlian (lewat kompetensinya), dibuat guru.
 *
 * @property int $id
 * @property int $kompetensi_id
 * @property string $judul
 * @property int|null $dibuat_oleh
 * @property CarbonImmutable|null $diubah_terakhir
 * @property int $level Level 1-4 yang dicapai siswa jika lulus kuis materi ini.
 * @property int $nilai_minimal Nilai minimal lulus kuis (bawaan 75).
 * @property bool $terverifikasi_industri
 * @property-read Kompetensi $kompetensi
 * @property-read User|null $pembuat
 */
#[Table('materi')]
#[Fillable(['kompetensi_id', 'judul', 'level', 'nilai_minimal', 'dibuat_oleh', 'diubah_terakhir', 'terverifikasi_industri'])]
class Materi extends Model
{
    /** @use HasFactory<MateriFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diubah_terakhir' => 'datetime',
            'level' => 'integer',
            'nilai_minimal' => 'integer',
            'terverifikasi_industri' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Kompetensi, $this>
     */
    public function kompetensi(): BelongsTo
    {
        return $this->belongsTo(Kompetensi::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * @return HasMany<LangkahMateri, $this>
     */
    public function langkah(): HasMany
    {
        return $this->hasMany(LangkahMateri::class)->orderBy('urutan');
    }

    /**
     * @return HasOne<Kuis, $this>
     */
    public function kuis(): HasOne
    {
        return $this->hasOne(Kuis::class);
    }

    /**
     * @return HasMany<VerifikasiMateri, $this>
     */
    public function verifikasi(): HasMany
    {
        return $this->hasMany(VerifikasiMateri::class);
    }
}
