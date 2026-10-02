<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu percobaan kuis oleh siswa.
 *
 * @property int $id
 * @property int $siswa_id
 * @property int $kuis_id
 * @property int $skor Persentase 0-100.
 * @property bool $lulus
 * @property CarbonImmutable $tanggal
 * @property-read User $siswa
 * @property-read Kuis $kuis
 */
#[Table('hasil_assessment')]
#[Fillable(['siswa_id', 'kuis_id', 'skor', 'lulus', 'tanggal'])]
class HasilAssessment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skor' => 'integer',
            'lulus' => 'boolean',
            'tanggal' => 'datetime',
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
     * @return BelongsTo<Kuis, $this>
     */
    public function kuis(): BelongsTo
    {
        return $this->belongsTo(Kuis::class);
    }
}
