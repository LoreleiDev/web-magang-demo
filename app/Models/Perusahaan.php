<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nama
 * @property string|null $alamat
 * @property string|null $bidang_usaha
 * @property list<string> $daftar_unit_kerja
 */
#[Table('perusahaan')]
#[Fillable(['nama', 'alamat', 'bidang_usaha', 'daftar_unit_kerja'])]
class Perusahaan extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daftar_unit_kerja' => 'array',
        ];
    }

    /**
     * @return HasMany<KelompokMagang, $this>
     */
    public function kelompokMagang(): HasMany
    {
        return $this->hasMany(KelompokMagang::class);
    }

    /**
     * @return HasMany<ProfilIndustri, $this>
     */
    public function profilIndustri(): HasMany
    {
        return $this->hasMany(ProfilIndustri::class);
    }

    /**
     * Dokumen industri untuk knowledge base AI Mentor.
     *
     * @return HasMany<DokumenKnowledgeBase, $this>
     */
    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenKnowledgeBase::class);
    }
}
