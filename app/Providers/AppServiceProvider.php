<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PortalApp;
use PortalRuntime;

use function Illuminate\Support\defer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * El núcleo del portal (carpeta portal/) se registra como un servicio único por petición.
     */
    public function register(): void
    {
        $this->app->singleton(PortalApp::class, function ($app) {
            // Los ajustes SMTP cifrados se guardan junto a este archivo virtual: storage/app/portal/.smtp-settings.json
            return new PortalApp($app['config']->get('portal'), PortalRuntime::storagePath().'/config.php');
        });
    }

    public function boot(): void
    {
        // En consola (tareas programadas) no hay sesión HTTP: el núcleo trabaja con un estado vacío.
        if ($this->app->runningInConsole() && ! isset($_SESSION)) {
            $_SESSION = [];
        }
        PortalRuntime::$publicPath = public_path();
        PortalRuntime::$storagePath = storage_path('app/portal');
        // Tareas como el primer intento de envío SMTP se ejecutan después de entregar la respuesta.
        PortalRuntime::$defer = static function (callable $task): void {
            defer($task);
        };
    }
}
