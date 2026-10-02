<?php

namespace App\Models;

use App\Enums\Role;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property Role $role
 * @property bool $status_aktif
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ProfilSiswa|null $profilSiswa
 * @property-read ProfilGuru|null $profilGuru
 * @property-read ProfilIndustri|null $profilIndustri
 */
#[Fillable(['name', 'email', 'password', 'role', 'status_aktif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'status_aktif' => 'boolean',
        ];
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * @return HasOne<ProfilSiswa, $this>
     */
    public function profilSiswa(): HasOne
    {
        return $this->hasOne(ProfilSiswa::class);
    }

    /**
     * @return HasOne<ProfilGuru, $this>
     */
    public function profilGuru(): HasOne
    {
        return $this->hasOne(ProfilGuru::class);
    }

    /**
     * @return HasOne<ProfilIndustri, $this>
     */
    public function profilIndustri(): HasOne
    {
        return $this->hasOne(ProfilIndustri::class);
    }

    /**
     * Kelompok magang yang dibimbing guru ini.
     *
     * @return HasMany<KelompokMagang, $this>
     */
    public function kelompokDibimbing(): HasMany
    {
        return $this->hasMany(KelompokMagang::class, 'guru_pembimbing_id');
    }

    /**
     * @return HasMany<ProgresKompetensi, $this>
     */
    public function progresKompetensi(): HasMany
    {
        return $this->hasMany(ProgresKompetensi::class, 'siswa_id');
    }

    /**
     * @return HasMany<Logbook, $this>
     */
    public function logbook(): HasMany
    {
        return $this->hasMany(Logbook::class, 'siswa_id');
    }

    /**
     * @return HasMany<HasilAssessment, $this>
     */
    public function hasilAssessment(): HasMany
    {
        return $this->hasMany(HasilAssessment::class, 'siswa_id');
    }

    /**
     * Program keahlian milik siswa atau guru (null untuk role lain).
     */
    public function programKeahlian(): ?string
    {
        return match ($this->role) {
            Role::Siswa => $this->profilSiswa?->program_keahlian,
            Role::Guru => $this->profilGuru?->program_keahlian,
            default => null,
        };
    }

    /**
     * Perusahaan tempat siswa magang (dari kelompok) atau tempat akun industri bekerja.
     */
    public function perusahaanId(): ?int
    {
        return match ($this->role) {
            Role::Siswa => $this->profilSiswa?->kelompok?->perusahaan_id,
            Role::Industri => $this->profilIndustri?->perusahaan_id,
            default => null,
        };
    }

    /**
     * Program keahlian siswa yang magang di perusahaan akun industri ini.
     * Menentukan materi yang boleh dilihat & diverifikasi industri.
     *
     * @return list<string>
     */
    public function programKeahlianSiswaPerusahaan(): array
    {
        $perusahaanId = $this->perusahaanId();

        if (! $this->hasRole(Role::Industri) || $perusahaanId === null) {
            return [];
        }

        return array_values(ProfilSiswa::query()
            ->whereHas('kelompok', fn (Builder $q) => $q->where('perusahaan_id', $perusahaanId))
            ->distinct()
            ->get(['program_keahlian'])
            ->map(fn (ProfilSiswa $profil) => $profil->program_keahlian)
            ->all());
    }

    /**
     * Siswa yang boleh dilihat oleh $viewer (CLAUDE.md bagian 2.1, baris "Lihat daftar siswa").
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function siswaDalamCakupan(Builder $query, User $viewer): void
    {
        $query->where('role', Role::Siswa);

        match ($viewer->role) {
            Role::Superadmin => null,
            Role::Guru => $query->whereHas(
                'profilSiswa.kelompok',
                fn (Builder $q) => $q->where('guru_pembimbing_id', $viewer->id),
            ),
            Role::Industri => $query->whereHas(
                'profilSiswa.kelompok',
                fn (Builder $q) => $q->where('perusahaan_id', $viewer->perusahaanId() ?? 0),
            ),
            Role::Siswa => $query->whereKey($viewer->id),
        };
    }

    /**
     * Apakah $viewer boleh melihat data siswa ini.
     */
    public function dapatDilihatOleh(User $viewer): bool
    {
        return self::query()->siswaDalamCakupan($viewer)->whereKey($this->id)->exists();
    }
}
