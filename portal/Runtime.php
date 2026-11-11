<?php
declare(strict_types=1);

/**
 * Puente entre el núcleo del portal y el entorno que lo ejecuta.
 *
 * Dentro de Laravel, App\Providers\PortalServiceProvider y el middleware PortalSession
 * conectan estas funciones con la sesión, las tareas diferidas y las rutas de Laravel.
 * Sin Laravel (pruebas de integración por CLI) se usa la sesión nativa de PHP.
 */
final class PortalRuntime
{
    /** @var (Closure():void)|null */
    public static ?Closure $regenerateSession = null;
    /** @var (Closure():string)|null */
    public static ?Closure $sessionId = null;
    /** @var (Closure(callable):void)|null */
    public static ?Closure $defer = null;
    public static ?string $publicPath = null;
    public static ?string $storagePath = null;

    /** Cambia el identificador de sesión y descarta el anterior (inicio y cierre de sesión, restauraciones). */
    public static function regenerateSession(): void
    {
        if (self::$regenerateSession) { (self::$regenerateSession)(); return; }
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
    }

    public static function sessionId(): string
    {
        return self::$sessionId ? (self::$sessionId)() : session_id();
    }

    /** Ejecuta la tarea después de entregar la respuesta; en CLI se ejecuta de inmediato. */
    public static function defer(callable $task): void
    {
        if (self::$defer && PHP_SAPI !== 'cli') { (self::$defer)(fn () => self::run($task)); return; }
        self::run($task);
    }

    public static function run(callable $task): void
    {
        try { $task(); } catch (Throwable $error) { error_log('Portal: tarea diferida fallida: '.$error->getMessage()); }
    }

    /** Raíz pública (carpeta public/ de Laravel). */
    public static function publicPath(): string
    {
        return self::$publicPath ?? dirname(__DIR__).'/public';
    }

    /** Carpeta privada con escritura: bloqueo de instalación, respaldos y ajustes SMTP cifrados. */
    public static function storagePath(): string
    {
        $path = self::$storagePath ?? dirname(__DIR__).'/storage/app/portal';
        if (!is_dir($path) && !@mkdir($path, 0700, true) && !is_dir($path)) {
            throw new PortalError(503, 'No se pudo crear la carpeta privada storage/app/portal. Revisa los permisos de storage/.');
        }
        return $path;
    }

    /** Archivos de solo lectura del núcleo: esquema SQL, contenido inicial y contratos. */
    public static function resource(string $file): string
    {
        return __DIR__.'/resources/'.$file;
    }
}
