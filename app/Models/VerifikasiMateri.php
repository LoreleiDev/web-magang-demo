<?php

namespace App\Models;

use App\Enums\HasilVerifikasiMateri;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat pemeriksaan materi oleh industri (CLAUDE.md bagian 11.2).
 *
 * @property int $id
 * @property int $materi_id
 * @property int|null $diperiksa_oleh
 * @property string|null $nama_pemeriksa
 * @property int $perusahaan_id
 * @property HasilVerifikasiMateri $hasil
 * @property string|null $masukan
 * @property bool $email_terkirim
 */
#[Table('verifikasi_materi')]
#[Fillable(['materi_id', 'diperiksa_oleh', 'nama_pemeriksa', 'perusahaan_id', 'hasil', 'masukan', 'email_terkirim'])]
class VerifikasiMateri extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hasil' => HasilVerifikasiMateri::class,
            'email_terkirim' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperiksa_oleh');
    }

    /**
     * @return BelongsTo<Perusahaan, $this>
     */
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class);
    }
}
