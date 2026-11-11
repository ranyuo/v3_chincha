<?php

namespace App\Exceptions;

use App\Http\Controllers\PortalApiController;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use PDOException;
use PortalError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Convierte los errores en respuestas del portal: JSON {message} para /api/... y una página
 * sencilla para el resto, con los mismos textos que la versión anterior.
 */
final class PortalErrors
{
    public const NOT_INSTALLED = 'El portal aún no está instalado. Abre /instalar para crear las tablas y la cuenta administradora.';

    /** Estado y mensaje públicos de un error; null si Laravel debe mostrar su página de depuración. */
    public static function describe(Throwable $error): ?array
    {
        if ($error instanceof PortalError) {
            return [$error->status, $error->getMessage()];
        }
        if ($error instanceof TokenMismatchException) {
            return [419, 'La sesión caducó. Recarga la página e inténtalo de nuevo.'];
        }
        if ($error instanceof PostTooLargeException) {
            return [413, 'La solicitud es demasiado grande. Máximo 40 MB de adjuntos en total.'];
        }
        if ($error instanceof NotFoundHttpException) {
            return [404, 'Página no encontrada.'];
        }
        if ($error instanceof MethodNotAllowedHttpException) {
            return [405, 'Método no permitido.'];
        }
        if ($error instanceof HttpExceptionInterface) {
            return [$error->getStatusCode(), $error->getMessage() ?: 'No se pudo completar la operación.'];
        }
        // Base vacía (instalación nueva): se indica el instalador en lugar del error genérico.
        if ($error instanceof PDOException && $error->getCode() === '42S02') {
            return [503, self::NOT_INSTALLED];
        }
        if (config('app.debug')) {
            return null;
        }

        return [503, 'No se pudo completar la operación. Revisa la configuración de MySQL o consulta al responsable del sitio.'];
    }

    public static function render(Throwable $error, Request $request): ?Response
    {
        $described = self::describe($error);
        if ($described === null) {
            return null;
        }
        [$status, $message] = $described;
        if ($request->is('api/*')) {
            return PortalApiController::json(['message' => $message], $status)->withHeaders(['Cache-Control' => 'no-store, private']);
        }

        return response()->view('error', ['message' => $message], $status)->withHeaders(['Cache-Control' => 'no-store, private']);
    }
}
