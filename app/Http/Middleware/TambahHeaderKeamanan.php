<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar untuk semua halaman web.
 */
class TambahHeaderKeamanan
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $header = $response->headers;

        // Cegah halaman dimuat di iframe situs lain (clickjacking).
        $header->set('X-Frame-Options', 'SAMEORIGIN', false);
        // Browser tidak boleh menebak jenis file dari isinya.
        $header->set('X-Content-Type-Options', 'nosniff', false);
        $header->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        // Website tidak memakai kamera, mikrofon, lokasi, atau pembayaran.
        $header->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()', false);

        // HTTPS saja selama 1 tahun, hanya saat production dan sudah lewat HTTPS.
        if ($request->isSecure() && app()->isProduction()) {
            $header->set('Strict-Transport-Security', 'max-age=31536000', false);
        }

        return $response;
    }
}
