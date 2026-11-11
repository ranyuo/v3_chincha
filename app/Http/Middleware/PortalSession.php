<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PortalRuntime;
use Symfony\Component\HttpFoundation\Response;

/**
 * Conecta la sesión de Laravel con el núcleo del portal.
 *
 * El núcleo (portal/) guarda su estado de sesión en $_SESSION: usuario, versión de credenciales,
 * desafíos de los formularios públicos y visitas. Este middleware carga ese arreglo desde la
 * sesión de Laravel antes de la petición y, al terminar, aplica a la sesión de Laravel solo lo que
 * el núcleo cambió; lo que los controladores guardan con session()/with() se conserva.
 *
 * El token CSRF del núcleo ($_SESSION['csrf']) es el mismo token de Laravel, de modo que
 * ValidateCsrfToken protege la API (cabecera X-CSRF-TOKEN) y los formularios (@csrf).
 */
class PortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();

        PortalRuntime::$sessionId = static fn (): string => $session->getId();
        PortalRuntime::$regenerateSession = static function () use ($session): void {
            $session->migrate(true);
        };

        $before = $session->all();
        unset($before['_token']);
        $_SESSION = $before;
        $_SESSION['csrf'] = $session->token();
        $token = $_SESSION['csrf'];

        try {
            return $next($request);
        } finally {
            $after = is_array($_SESSION ?? null) ? $_SESSION : [];
            $newToken = is_string($after['csrf'] ?? null) && $after['csrf'] !== '' ? $after['csrf'] : null;
            unset($after['csrf'], $after['_token']);

            foreach (array_keys($before) as $key) {
                if (! array_key_exists($key, $after)) {
                    $session->forget($key);
                }
            }
            foreach ($after as $key => $value) {
                if (! array_key_exists($key, $before) || $before[$key] !== $value) {
                    $session->put($key, $value);
                }
            }
            // Al iniciar o cerrar sesión el núcleo genera un token nuevo: Laravel lo adopta.
            if ($newToken === null) {
                $session->regenerateToken();
            } elseif ($newToken !== $token) {
                $session->put('_token', $newToken);
            }
            $_SESSION = [];
        }
    }
}
