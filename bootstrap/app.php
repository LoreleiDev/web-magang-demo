<?php

use App\Http\Middleware\EnsurePendampinganDimulai;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'pendampingan' => EnsurePendampinganDimulai::class,
        ]);

        // Halaman siswa mengarah ke login khusus siswa.
        // Tamu diarahkan ke halaman login sesuai area yang dibuka (keputusan 13 no. 32).
        $middleware->redirectGuestsTo(fn (Request $request) => route(match (true) {
            $request->is('guru', 'guru/*') => 'login.guru',
            $request->is('industri', 'industri/*') => 'login.industri',
            $request->is('superadmin', 'superadmin/*') => 'login.admin',
            default => 'login',
        }));

        // Pengguna yang sudah login dan membuka /login diarahkan ke halaman role-nya.
        $middleware->redirectUsersTo(
            fn (Request $request) => route($request->user()->role->homeRoute()),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
