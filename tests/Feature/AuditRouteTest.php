<?php

use App\Enums\JenisDokumen;
use App\Models\DokumenKnowledgeBase;
use App\Models\KelompokMagang;
use App\Models\Kompetensi;
use App\Models\Logbook;
use App\Models\Materi;
use App\Models\ProgramKeahlian;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;

/*
| Audit hak akses (CLAUDE.md bagian 2.1 & 14) untuk SEMUA route, termasuk
| POST/PUT/DELETE, lewat URL langsung. Route baru otomatis ikut diperiksa.
*/

/** Route yang memang boleh dibuka tanpa role tertentu. */
const ROUTE_TANPA_ROLE = [
    'home', 'logout', 'logbook.bukti',
    'login', 'login.store', 'login.guru', 'login.guru.store',
    'login.industri', 'login.industri.store', 'login.admin', 'login.admin.store',
];

/**
 * @return list<Route>
 */
function routeAplikasi(): array
{
    return array_values(array_filter(
        Router::getRoutes()->getRoutes(),
        fn (Route $r) => $r->getName() !== null
            && ! Str::startsWith($r->getName(), ['storage.', 'boost.', 'wayfinder.'])
            && ! in_array($r->getName(), ROUTE_TANPA_ROLE, true),
    ));
}

function roleRoute(Route $route): ?string
{
    foreach ($route->gatherMiddleware() as $m) {
        if (is_string($m) && str_starts_with($m, 'role:')) {
            return Str::after($m, 'role:');
        }
    }

    return null;
}

beforeEach(function () {
    $guru = User::factory()->guru('TKJ')->create();
    $kelompok = KelompokMagang::factory()->create(['guru_pembimbing_id' => $guru->id]);
    $siswa = User::factory()->siswa($kelompok, 'TKJ', 'Jaringan')->create();
    $kompetensi = Kompetensi::factory()->create(['program_keahlian' => 'TKJ', 'dibuat_oleh' => $guru->id]);

    // Satu record untuk setiap parameter route, agar route model binding tidak 404.
    $this->parameter = [
        'siswa' => $siswa->id,
        'akun' => $siswa->id,
        'kelompok' => $kelompok->id,
        'perusahaan' => $kelompok->perusahaan_id,
        'kompetensi' => $kompetensi->id,
        'materi' => Materi::factory()->create(['kompetensi_id' => $kompetensi->id, 'dibuat_oleh' => $guru->id])->id,
        'logbook' => Logbook::create(['siswa_id' => $siswa->id, 'tanggal' => now()->toDateString(), 'aktivitas' => 'X'])->id,
        'programKeahlian' => ProgramKeahlian::where('kode', 'TKJ')->value('id'),
        'dokumen' => DokumenKnowledgeBase::create([
            'jenis' => JenisDokumen::Industri,
            'perusahaan_id' => $kelompok->perusahaan_id,
            'nama_file' => 'sop.pdf',
            'path_file' => 'kb/sop.pdf',
            'diunggah_oleh' => $guru->id,
        ])->id,
    ];

    $this->url = fn (Route $r) => route($r->getName(), array_intersect_key($this->parameter, array_flip($r->parameterNames())));
    $this->metode = fn (Route $r) => strtolower(collect($r->methods())->reject(fn ($m) => $m === 'HEAD')->first());
});

test('setiap route aplikasi memakai middleware auth dan role sesuai areanya', function () {
    $routes = routeAplikasi();

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $area = Str::before($route->uri(), '/');

        expect($route->gatherMiddleware())->toContain('auth');
        expect(roleRoute($route))->toBe($area, "Route {$route->getName()} harus memakai role:{$area}");
    }
});

test('tamu diarahkan ke halaman login di semua route', function () {
    foreach (routeAplikasi() as $route) {
        $respons = $this->{($this->metode)($route)}(($this->url)($route));

        expect($respons->status())->toBe(302, "Route {$route->getName()}")
            ->and($respons->headers->get('Location'))->toContain('/login');
    }
});

test('role lain ditolak (403) di semua route, termasuk aksi POST/PUT/DELETE', function () {
    $akun = [
        'superadmin' => User::factory()->superadmin()->create(),
        'guru' => User::factory()->guru('TKJ')->create(),
        'industri' => User::factory()->industri()->create(),
        'siswa' => User::factory()->siswa()->create(),
    ];

    $jumlah = 0;
    foreach (routeAplikasi() as $route) {
        foreach ($akun as $role => $user) {
            if ($role === roleRoute($route)) {
                continue;
            }

            $status = $this->actingAs($user)->{($this->metode)($route)}(($this->url)($route))->status();
            expect($status)->toBe(403, "{$role} membuka {$route->getName()}");
            $jumlah++;
        }
    }

    expect($jumlah)->toBeGreaterThan(200);
});
