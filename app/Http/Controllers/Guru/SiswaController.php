<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RingkasanSiswaService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Detail siswa di kelompok bimbingan guru (bagian 7). Level siswa naik otomatis
 * dari kuis (bagian 11.1); guru hanya melihat.
 */
class SiswaController extends Controller
{
    public function show(User $siswa, RingkasanSiswaService $ringkasan): Response
    {
        Gate::authorize('lihatSiswa', $siswa);

        return Inertia::render('guru/siswa', $ringkasan->detail($siswa));
    }
}
