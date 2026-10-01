<?php

namespace App\Models;

use Database\Factories\KompetensiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kompetensi umum per program keahlian, diinput guru.
 *
 * @property int $id
 * @property string $program_keahlian
 * @property string $nama_kompetensi_sekolah
 * @property string $aktivitas_kompetensi_industri
 * @property int $target_level
 * @property int $dibuat_oleh
 * @property-read User $pembuat
 */
#[Table('kompetensi')]
#[Fillable(['program_keahlian', 'nama_kompetensi_sekolah', 'aktivitas_kompetensi_industri', 'target_level', 'dibuat_oleh'])]
class Kompetensi extends Model
{
    /** @use HasFactory<KompetensiFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_level' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * @return HasMany<Materi, $this>
     */
    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class);
    }

    /**
     * @return HasMany<ProgresKompetensi, $this>
     */
    public function progres(): HasMany
    {
        return $this->hasMany(ProgresKompetensi::class);
    }
}
