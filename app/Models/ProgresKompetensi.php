<?php

namespace App\Models;

use App\Enums\StatusKompetensi;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Progres satu siswa pada satu kompetensi.
 *
 * @property int $id
 * @property int $siswa_id
 * @property int $kompetensi_id
 * @property int|null $level_siswa
 * @property int|null $level_diisi_oleh
 * @property Carbon|null $tanggal_level_diisi
 * @property StatusKompetensi $status
 * @property int|null $diverifikasi_oleh
 * @property Carbon|null $tanggal_verifikasi
 * @property-read User $siswa
 * @property-read Kompetensi $kompetensi
 */
#[Table('progres_kompetensi')]
#[Fillable([
    'siswa_id', 'kompetensi_id', 'level_siswa', 'level_diisi_oleh', 'tanggal_level_diisi',
    'status', 'diverifikasi_oleh', 'tanggal_verifikasi',
])]
class ProgresKompetensi extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'belum_dipelajari',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level_siswa' => 'integer',
            'tanggal_level_diisi' => 'datetime',
            'status' => StatusKompetensi::class,
            'tanggal_verifikasi' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
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
    public function pengisiLevel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'level_diisi_oleh');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }
}
