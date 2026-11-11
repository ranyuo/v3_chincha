<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PortalApp;
use PortalError;
use Symfony\Component\HttpFoundation\Response;

/**
 * Con una contraseña temporal solo se permite cargar el portal, cambiar la contraseña,
 * cerrar sesión y registrar visitas o eventos de interfaz.
 */
class PortalApiGuard
{
    private const ALLOWED_WITH_TEMPORARY_PASSWORD = ['api/password', 'api/session', 'api/visitas', 'api/eventos', 'api/archivo', 'api/archivos'];

    public function __construct(private readonly PortalApp $portal) {}

    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();
        $allowed = in_array($path, self::ALLOWED_WITH_TEMPORARY_PASSWORD, true) || ($path === 'api/portal' && $request->isMethod('GET'));
        if (! $allowed && ! empty($this->portal->user()['cambiarClave'])) {
            throw new PortalError(403, 'Cambia tu contraseña temporal antes de continuar.');
        }

        return $next($request);
    }
}
