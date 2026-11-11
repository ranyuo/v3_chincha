<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PortalApp;
use PortalError;

/** Instalación inicial: crea las tablas y la primera cuenta administradora. */
class InstallController extends Controller
{
    public function __invoke(Request $request, PortalApp $portal): Response
    {
        $error = '';
        $success = false;
        $status = 200;
        $closed = $portal->installed();
        if ($closed) {
            $status = 410;
        } elseif ($request->isMethod('POST')) {
            try {
                $portal->install($request->post());
                $success = true;
            } catch (PortalError $exception) {
                $error = $exception->getMessage();
                $status = $exception->status;
            }
        }

        return response()->view('admin.install', compact('error', 'success', 'closed'), $status);
    }
}
