<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use PortalApp;

/** Página única del portal: sitio público e intranet (la navegación interna usa #/ruta). */
class PortalPageController extends Controller
{
    /** Servicios que también se publican en su propia dirección (por ejemplo /mesa-de-partes). */
    public const SERVICES = [
        'mesa-de-partes' => 'servicios/mesa-de-partes',
        'libro-de-reclamaciones' => 'servicios/libro-de-reclamaciones',
        'bolsa-laboral' => 'servicios/bolsa-laboral',
        'certificados' => 'servicios/consulta-certificados',
    ];

    public function __construct(private readonly PortalApp $portal) {}

    public function home(): Response
    {
        return $this->render($this->portal->bootstrap());
    }

    public function service(string $service): Response
    {
        $bootstrap = $this->portal->bootstrap();
        $bootstrap['standaloneService'] = self::SERVICES[$service];

        return $this->render($bootstrap);
    }

    private function render(array $bootstrap): Response
    {
        return response()->view('portal', ['bootstrap' => $bootstrap]);
    }
}
