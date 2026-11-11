<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tareas del portal
|--------------------------------------------------------------------------
| En el hosting basta una sola tarea cron cada minuto:
|   php /ruta/al/proyecto/artisan schedule:run
*/

// Antes portal-private/mail-worker.php: reintentos de la cola de correo y depuración diaria de auditoría.
Artisan::command('portal:correo {--limit=10 : Mensajes por ejecución}', function () {
    try {
        $portal = app(PortalApp::class);
        // La depuración diaria de auditoría no debe impedir el envío de correo.
        try {
            PortalAudit::purge($portal);
        } catch (Throwable) {
            $this->error('No se pudo depurar la auditoría antigua.');
        }
        $result = $portal->processMail(max(1, (int) $this->option('limit')));
        $this->line(json_encode($result, JSON_THROW_ON_ERROR));

        return ($result['configured'] ?? true) === false || ($result['failed'] ?? 0) > 0 ? 1 : 0;
    } catch (Throwable) {
        $this->error('No se pudo procesar la cola de correo. Revisa configuración y actualización de la base.');

        return 1;
    }
})->purpose('Envía la cola de notificaciones por correo (SMTP)');

// Antes portal-private/cron.php: respaldo diario según el horario elegido en la intranet.
Artisan::command('portal:respaldos', function () {
    try {
        $portal = app(PortalApp::class);
        $this->line(PortalBackups::scheduled($portal) ? 'Respaldo programado completado.' : 'Sin respaldos pendientes.');
        $purged = PortalAudit::purge($portal);
        if ($purged) {
            $this->line("Auditoría depurada: $purged filas.");
        }

        return 0;
    } catch (Throwable) {
        $this->error('No se pudo crear el respaldo programado. Revisa permisos y espacio privado.');

        return 1;
    }
})->purpose('Crea el respaldo programado y depura la auditoría antigua');

Schedule::command('portal:correo')->everyMinute()->withoutOverlapping();
Schedule::command('portal:respaldos')->hourly()->withoutOverlapping();
