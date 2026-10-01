<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $kuis_id
 * @property int $urutan
 * @property string $pertanyaan
 * @property list<string> $pilihan
 * @property int $jawaban_benar Indeks (mulai 0) pada `pilihan`.
 * @property string|null $pembahasan
 */
#[Table('soal_kuis')]
#[Fillable(['kuis_id', 'urutan', 'pertanyaan', 'pilihan', 'jawaban_benar', 'pembahasan'])]
class SoalKuis extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'pilihan' => 'array',
            'jawaban_benar' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Kuis, $this>
     */
    public function kuis(): BelongsTo
    {
        return $this->belongsTo(Kuis::class);
    }
}
