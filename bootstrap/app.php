<?php

use App\Http\Middleware\EnsurePendampinganDimulai;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\TambahHeaderKeamanan;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

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
            TambahHeaderKeamanan::class,
        ]);

        // Proxy yang dipercaya diatur dari config('magang.trusted_proxies') di
        // AppServiceProvider (agar tetap terbaca setelah config:cache).

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

        // Halaman error berbahasa Indonesia sesuai tampilan website. Saat lokal, error
        // server tetap memakai halaman debug Laravel agar mudah dilacak.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if ($request->expectsJson()) {
                return $response;
            }

            if ($status === 419) {
                Inertia::flash('toast', ['type' => 'error', 'message' => 'Halaman sudah kedaluwarsa. Silakan coba lagi.']);

                return back();
            }

            if (in_array($status, [403, 404, 429, 500, 503], true) && ! ($status >= 500 && config('app.debug'))) {
                return Inertia::render('error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
