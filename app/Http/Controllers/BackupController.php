<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PortalApp;
use PortalAudit;
use PortalBackups;
use PortalError;
use Symfony\Component\HttpFoundation\Response;

/** Respaldos: crear, descargar, programar y restaurar copias completas. */
class BackupController extends Controller
{
    public function __construct(private readonly PortalApp $portal) {}

    public function index(Request $request): Response
    {
        $this->portal->requireAdmin();
        if ($request->query->has('download')) {
            $id = is_string($request->query('download')) ? $request->query('download') : '';
            $path = PortalBackups::path($id);
            if (! is_file($path)) {
                throw new PortalError(404, 'Respaldo no encontrado.');
            }
            PortalAudit::write($this->portal, 'Descargar respaldo portable', 'Respaldos', $id);

            return response()->download($path, $id.'.piscobak', ['Content-Type' => 'application/gzip']);
        }

        return $this->page((string) $request->session()->get('portal_backup_notice', ''));
    }

    public function action(Request $request): Response
    {
        $actor = $this->portal->requireAdmin();
        try {
            $message = match ($request->post('action')) {
                'create' => $this->create(),
                'configure' => $this->configure($request),
                'run' => PortalBackups::scheduled($this->portal) ? 'Respaldo programado completado.' : 'No hay un respaldo programado pendiente para hoy.',
                'restore' => null,
                default => throw new PortalError(422, 'Acción no válida.'),
            };
            if ($message === null) {
                $this->restore($request, $actor);

                return $this->page('Restauración completada. Se conservaron los archivos y el historial de auditoría del servidor. Por seguridad se cerraron las sesiones: entra con una cuenta incluida en el respaldo.', restored: true);
            }

            return redirect()->route('backups', status: 303)->with('portal_backup_notice', $message);
        } catch (PortalError $exception) {
            return $this->page('', $exception->getMessage(), $exception->status);
        }
    }

    /** Exportación SQL completa (antes backup.php). */
    public function sql(): Response
    {
        $admin = $this->portal->requireAdmin();

        return response()->streamDownload(function () use ($admin) {
            PortalBackups::sqlDump($this->portal, $admin, function (string $chunk): void {
                echo $chunk;
            });
        }, 'portal-chincha-'.date('Ymd-His').'.sql', ['Content-Type' => 'application/sql; charset=utf-8']);
    }

    private function create(): string
    {
        PortalBackups::create($this->portal);

        return 'Respaldo completo creado. Puedes descargarlo o restaurarlo desde esta pantalla.';
    }

    private function configure(Request $request): string
    {
        PortalBackups::configure($this->portal, ['enabled' => $request->has('enabled'), 'hour' => $request->post('hour'), 'retention' => $request->post('retention')]);

        return 'Programación guardada. La tarea programada de Laravel ejecutará los respaldos según este horario.';
    }

    private function restore(Request $request, array $actor): void
    {
        if ($request->post('confirmation') !== 'RESTAURAR') {
            throw new PortalError(422, 'Escribe RESTAURAR para confirmar.');
        }
        $stmt = $this->portal->db()->prepare('SELECT password FROM pisco_users WHERE id=?');
        $stmt->execute([$actor['server_id']]);
        $password = $request->post('password');
        if (! is_string($password) || ! password_verify($password, (string) $stmt->fetchColumn())) {
            throw new PortalError(422, 'La contraseña actual no es correcta.');
        }
        $file = $request->file('backup');
        if ($file !== null || ($_FILES['backup']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($file === null || ! $file->isValid()) {
                throw new PortalError(422, 'No se pudo recibir el respaldo.');
            }
            $source = $file->getRealPath();
        } else {
            $source = PortalBackups::path(is_string($request->post('backup_id')) ? $request->post('backup_id') : '');
            if (! is_file($source)) {
                throw new PortalError(404, 'Selecciona o sube un respaldo existente.');
            }
        }
        PortalBackups::create($this->portal, 'previo-restauracion');
        PortalBackups::restore($this->portal, $source);
    }

    private function page(string $message, string $error = '', int $status = 200, bool $restored = false): Response
    {
        return response()->view('admin.backups', [
            'message' => $message,
            'error' => $error,
            'restored' => $restored,
            'schedule' => $restored ? null : PortalBackups::config($this->portal),
            'backups' => $restored ? null : PortalBackups::listing(),
            'timezone' => date_default_timezone_get(),
        ], $status);
    }
}
