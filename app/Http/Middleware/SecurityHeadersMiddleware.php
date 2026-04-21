<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    // Middleware header keamanan untuk melindungi dari serangan web umum (XSS, Clickjacking, MIME sniffing, dll)
    public function handle(Request $request, Closure $next): Response
    {
        // Membuat nilai CSP Acak (nonce) untuk skrip/gaya bawaan Vite
        Vite::useCspNonce();
        $nonce = Vite::cspNonce();

        $response = $next($request);

        // Kebijakan Keamanan Konten (CSP) — mencegah XSS dengan hanya mengizinkan sumber yang tepercaya
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: blob: https://res.cloudinary.com",
            "connect-src 'self' https://*.pusherplatform.io https://*.pusher.com wss://*.pusher.com",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        // Mencegah Clickjacking — halaman tidak dapat disematkan di dalam iframe
        $response->headers->set('X-Frame-Options', 'DENY');

        // Mencegah MIME sniffing — browser harus menghormati tipe konten yang dideklarasikan
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Perlindungan XSS untuk browser versi lama
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Mengelola informasi Referrer yang dikirimkan dalam permintaan HTTP
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Menonaktifkan fitur browser yang tidak perlu guna mengurangi celah keamanan
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Mewajibkan protokol HTTPS pada server produksi (HSTS)
        if (App::environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
