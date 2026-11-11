<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JsonException;
use PortalApp;
use PortalError;
use PortalPolicy;
use Symfony\Component\HttpFoundation\Response;

/** Roles y permisos: nombre y módulos habilitados de cada perfil. */
class RolesController extends Controller
{
    public function __invoke(Request $request, PortalApp $portal): Response
    {
        $portal->requireAdmin();
        $error = '';
        $status = 200;
        $input = [];
        if ($request->isMethod('POST')) {
            try {
                try {
                    $expected = json_decode((string) $request->post('expected', 'null'), true, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    throw new PortalError(422, 'Solicitud no válida.');
                }
                $input = ['role' => $request->post('role'), 'label' => $request->post('label', ''), 'mods' => $request->post('mods', []),
                    'expected' => $expected, 'reset' => $request->post('action') === 'reset'];
                $portal->saveRole($input);

                return redirect()->route('roles', ['role' => $input['role']], 303)
                    ->with('roles_notice', 'Rol actualizado. Sus usuarios deberán iniciar sesión de nuevo para aplicar los permisos.');
            } catch (PortalError $exception) {
                if (! in_array($exception->status, [409, 422], true)) {
                    throw $exception;
                }
                $error = $exception->getMessage();
                $status = $exception->status;
            }
        }

        $overrides = $portal->roleSettings();
        $definitions = PortalPolicy::defaults();
        $roles = PortalPolicy::definitions()['roles'];
        $key = is_string($input['role'] ?? null) ? $input['role'] : (is_string($request->query('role')) ? $request->query('role') : 'imagen');
        if (! isset($roles[$key])) {
            $key = 'imagen';
        }

        return response()->view('admin.roles', [
            'notice' => (string) $request->session()->get('roles_notice', ''),
            'error' => $error,
            'roles' => $roles,
            'key' => $key,
            'role' => $roles[$key],
            'protected' => $key === 'admin',
            'definitions' => $definitions,
            'expected' => json_encode($overrides[$key] ?? null, JSON_THROW_ON_ERROR),
        ], $status);
    }
}
