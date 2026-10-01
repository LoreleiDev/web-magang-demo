<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nama_kelompok
 * @property int $perusahaan_id
 * @property int $guru_pembimbing_id
 * @property Carbon $periode_mulai
 * @property Carbon $periode_selesai
 * @property-read Perusahaan $perusahaan
 * @property-read User $guruPembimbing
 */
#[Table('kelompok_magang')]
#[Fillable(['nama_kelompok', 'perusahaan_id', 'guru_pembimbing_id', 'periode_mulai', 'periode_selesai'])]
class KelompokMagang extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Perusahaan, $this>
     */
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function guruPembimbing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_pembimbing_id');
    }

    /**
     * @return HasMany<ProfilSiswa, $this>
     */
    public function profilSiswa(): HasMany
    {
        return $this->hasMany(ProfilSiswa::class, 'kelompok_id');
    }

    /**
     * Akun siswa anggota kelompok.
     *
     * @return HasManyThrough<User, ProfilSiswa, $this>
     */
    public function siswa(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, ProfilSiswa::class, 'kelompok_id', 'id', 'id', 'user_id');
    }
}
