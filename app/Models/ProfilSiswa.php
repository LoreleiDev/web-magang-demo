<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Perusahaan dan periode magang siswa diambil dari kelompoknya.
 *
 * @property int $id
 * @property int $user_id
 * @property string $id_siswa
 * @property string $program_keahlian
 * @property string|null $unit_kerja
 * @property int|null $kelompok_id
 * @property-read User $user
 * @property-read KelompokMagang|null $kelompok
 */
#[Table('profil_siswa')]
#[Fillable(['user_id', 'id_siswa', 'program_keahlian', 'unit_kerja', 'kelompok_id'])]
class ProfilSiswa extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<KelompokMagang, $this>
     */
    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(KelompokMagang::class, 'kelompok_id');
    }
}
