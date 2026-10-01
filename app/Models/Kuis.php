<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $materi_id
 * @property-read Materi $materi
 */
#[Table('kuis')]
#[Fillable(['materi_id'])]
class Kuis extends Model
{
    /**
     * @return BelongsTo<Materi, $this>
     */
    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    /**
     * @return HasMany<SoalKuis, $this>
     */
    public function soal(): HasMany
    {
        return $this->hasMany(SoalKuis::class)->orderBy('urutan');
    }

    /**
     * @return HasMany<HasilAssessment, $this>
     */
    public function hasil(): HasMany
    {
        return $this->hasMany(HasilAssessment::class);
    }
}
