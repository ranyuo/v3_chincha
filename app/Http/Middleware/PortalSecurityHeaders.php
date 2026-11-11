<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Cabeceras de seguridad y caché del portal (antes portal_headers()). */
class PortalSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;
        $headers->set('Cache-Control', 'no-store, private');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        // HSTS solo sobre HTTPS para no bloquear pruebas locales por HTTP.
        if ($request->isSecure() || $request->headers->get('X-Forwarded-Proto') === 'https') {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
        $headers->remove('X-Powered-By');

        return $response;
    }
}
