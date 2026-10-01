<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $kode
 * @property string $nama
 */
#[Table('program_keahlian')]
#[Fillable(['kode', 'nama'])]
class ProgramKeahlian extends Model
{
    /**
     * Kode => nama, urut kode.
     *
     * @return array<string, string>
     */
    public static function opsi(): array
    {
        return self::orderBy('kode')->pluck('nama', 'kode')->all();
    }

    public static function nama(?string $kode): ?string
    {
        return $kode === null ? null : (self::opsi()[$kode] ?? $kode);
    }

    /**
     * Apakah kode ini masih dipakai siswa, guru, kompetensi, atau dokumen sekolah.
     */
    public function sedangDipakai(): bool
    {
        foreach (['profil_siswa', 'profil_guru', 'kompetensi', 'dokumen_knowledge_base'] as $tabel) {
            if (DB::table($tabel)->where('program_keahlian', $this->kode)->exists()) {
                return true;
            }
        }

        return false;
    }
}
