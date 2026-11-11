<?php

use App\Exceptions\PortalErrors;
use App\Http\Middleware\PortalApiGuard;
use App\Http\Middleware\PortalSecurityHeaders;
use App\Http\Middleware\PortalSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // El núcleo valida cada campo por su cuenta: las contraseñas y textos llegan tal como se escribieron.
        $middleware->remove([TrimStrings::class, ConvertEmptyStringsToNull::class]);
        $middleware->web(append: [PortalSecurityHeaders::class, PortalSession::class]);
        $middleware->alias(['portal.api' => PortalApiGuard::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Los errores esperados (validación, permisos, conflictos) no se registran en el log.
        $exceptions->dontReport([PortalError::class]);
        // Antes de instalar, las tablas no existen: la portada lo indica sin llenar el log.
        $exceptions->dontReportWhen(fn (Throwable $error) => $error instanceof PDOException && $error->getCode() === '42S02');
        $exceptions->render(fn (Throwable $error, Request $request) => PortalErrors::render($error, $request));
    })->create();
