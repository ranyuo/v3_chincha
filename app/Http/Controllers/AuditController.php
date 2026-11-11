<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\Request;
use PortalApp;
use PortalAudit;
use PortalError;
use Symfony\Component\HttpFoundation\Response;

/** Historial completo de auditoría: filtro, paginación y exportación CSV. */
class AuditController extends Controller
{
    private const COLUMNS = ['occurred_at', 'username', 'role', 'action', 'entity', 'record_key', 'details'];

    private const HEADINGS = ['Fecha', 'Usuario', 'Rol', 'Acción', 'Entidad', 'Registro', 'Detalle'];

    public function __invoke(Request $request, PortalApp $portal): Response
    {
        $portal->requireAdmin();
        $where = [];
        $args = [];
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';
        if (strlen($q) > 200) {
            throw new PortalError(422, 'Filtro demasiado largo.');
        }
        if ($q !== '') {
            $where[] = '(username LIKE ? OR entity LIKE ? OR record_key LIKE ? OR action LIKE ? OR CAST(details AS CHAR) LIKE ?)';
            array_push($args, ...array_fill(0, 5, '%'.$q.'%'));
        }
        $dates = [];
        foreach (['desde' => '>=', 'hasta' => '<='] as $key => $operator) {
            $value = $request->query($key, '');
            $dates[$key] = is_string($value) ? $value : '';
            if ($value === '') {
                continue;
            }
            $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
            if (! $date || $date->format('Y-m-d') !== $value) {
                throw new PortalError(422, 'Fecha de filtro no válida.');
            }
            $where[] = 'occurred_at '.$operator.' ?';
            $args[] = $value.($key === 'hasta' ? ' 23:59:59.999999' : ' 00:00:00');
        }
        $condition = $where ? ' WHERE '.implode(' AND ', $where) : '';

        if ($request->query('download') === 'csv') {
            PortalAudit::write($portal, 'Exportar auditoría', 'Auditoría', '', ['filtro' => $q]);

            return response()->streamDownload(function () use ($portal, $condition, $args) {
                $out = fopen('php://output', 'wb');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, self::HEADINGS, ';', '"', '');
                $last = PHP_INT_MAX;
                do {
                    $stmt = $portal->db()->prepare('SELECT * FROM pisco_audit'.$condition.($condition ? ' AND ' : ' WHERE ').'id < ? ORDER BY id DESC LIMIT 500');
                    $stmt->execute([...$args, $last]);
                    $batch = $stmt->fetchAll();
                    foreach ($batch as $row) {
                        $last = (int) $row['id'];
                        // Evita que una hoja de cálculo interprete el contenido como fórmula.
                        $values = array_map(fn ($v) => preg_match('/^[=+@\t\r-]/', (string) $v) ? "'".$v : $v, array_map(fn ($c) => $row[$c], self::COLUMNS));
                        fputcsv($out, $values, ';', '"', '');
                    }
                } while (count($batch) === 500);
                fclose($out);
            }, 'auditoria-'.date('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
        }

        $count = $portal->db()->prepare('SELECT COUNT(*) FROM pisco_audit'.$condition);
        $count->execute($args);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / 100));
        $page = max(1, min($pages, (int) $request->query('page', 1)));
        $stmt = $portal->db()->prepare('SELECT * FROM pisco_audit'.$condition.' ORDER BY id DESC LIMIT 100 OFFSET '.(($page - 1) * 100));
        $stmt->execute($args);

        $query = $request->query();
        $link = fn (array $patch) => '?'.http_build_query([...$query, ...$patch]);

        return response()->view('admin.audit', [
            'q' => $q, 'dates' => $dates, 'total' => $total, 'page' => $page, 'pages' => $pages,
            'rows' => $stmt->fetchAll(), 'columns' => self::COLUMNS, 'headings' => self::HEADINGS, 'link' => $link,
        ]);
    }
}
