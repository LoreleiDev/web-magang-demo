<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Logbook harian siswa; satu per tanggal.
 *
 * @property int $id
 * @property int $siswa_id
 * @property CarbonImmutable $tanggal
 * @property string $aktivitas
 * @property string|null $peralatan_software
 * @property string|null $sudah_dipahami
 * @property string|null $baru_ditemui
 * @property string|null $kesulitan
 * @property string|null $pengetahuan_sekolah_digunakan
 * @property string|null $ingin_dipelajari
 * @property string|null $bukti_kegiatan Path file di disk lokal.
 * @property array<string, list<string>>|null $hasil_analisis_ai
 * @property-read User $siswa
 */
#[Table('logbook')]
#[Fillable([
    'siswa_id', 'tanggal', 'aktivitas', 'peralatan_software', 'sudah_dipahami', 'baru_ditemui',
    'kesulitan', 'pengetahuan_sekolah_digunakan', 'ingin_dipelajari', 'bukti_kegiatan', 'hasil_analisis_ai',
])]
class Logbook extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'hasil_analisis_ai' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}
