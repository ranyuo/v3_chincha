<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PortalApp;
use PortalError;
use PortalPolicy;

/** Cuentas: lista y alta de usuarios (administrador) y cambio de la propia contraseña. */
class AccountsController extends Controller
{
    public function __invoke(Request $request, PortalApp $portal): Response
    {
        $actor = $portal->requireUser();
        $error = '';
        $status = 200;
        if ($request->isMethod('POST')) {
            try {
                if ($request->post('action') === 'password') {
                    $temporary = ! empty($actor['cambiarClave']);
                    $portal->changePassword($request->post());
                    if ($temporary) {
                        return redirect('/#/admin/dashboard', 303);
                    }
                    $notice = 'Contraseña actualizada.';
                } else {
                    $portal->createAccount($request->post());
                    $notice = 'Cuenta creada con su rol y vínculos.';
                }

                return redirect()->route('accounts', status: 303)->with('account_notice', $notice);
            } catch (PortalError $exception) {
                if (in_array($exception->status, [403, 419], true)) {
                    throw $exception;
                }
                $error = $exception->getMessage();
                $status = $exception->status;
            }
        }

        return response()->view('admin.accounts', [
            'actor' => $actor,
            'error' => $error,
            'notice' => (string) $request->session()->get('account_notice', ''),
            'users' => $actor['rol'] === 'admin' ? $portal->accounts() : [],
            'roles' => PortalPolicy::definitions()['roles'],
        ], $status);
    }
}
