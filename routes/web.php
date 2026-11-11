<?php

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PortalApiController;
use App\Http\Controllers\PortalPageController;
use App\Http\Controllers\RolesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal público e intranet
|--------------------------------------------------------------------------
| La aplicación del navegador navega con #/ruta dentro de una sola página.
*/

Route::get('/', [PortalPageController::class, 'home'])->name('portal');

// Servicios públicos con enlace propio, sin la navegación del portal.
Route::get('{service}', [PortalPageController::class, 'service'])
    ->whereIn('service', array_keys(PortalPageController::SERVICES))
    ->name('portal.service');

/*
|--------------------------------------------------------------------------
| API JSON (antes /index.php?route=api/...)
|--------------------------------------------------------------------------
| Las peticiones que no son GET llevan la cabecera X-CSRF-TOKEN.
*/

Route::prefix('api')->middleware('portal.api')->controller(PortalApiController::class)->group(function () {
    Route::get('portal', 'bootstrap');
    Route::put('portal', 'save');
    Route::post('session', 'login');
    Route::delete('session', 'logout');
    Route::post('password', 'changePassword');
    Route::get('archivo', 'file');
    Route::post('archivos', 'upload');
    Route::post('desafio', 'challenge');
    Route::post('eventos', 'interfaceEvent');
    Route::post('cuentas', 'accounts');
    Route::post('empresas', 'registerCompany');
    Route::post('visitas', 'track');
    Route::get('notificaciones', 'mailStatus');
    Route::post('notificaciones/reintentar', 'mailRetry');
    Route::get('smtp', 'smtpForm');
    Route::put('smtp', 'saveSmtp');
    Route::post('solicitudes/{type}', 'submit')->where('type', '[a-z]+');
    Route::post('consultas/{type}', 'lookup')->where('type', '[a-z]+');
});

/*
|--------------------------------------------------------------------------
| Páginas de administración
|--------------------------------------------------------------------------
*/

Route::match(['get', 'post'], 'instalar', InstallController::class)->name('install');
Route::match(['get', 'post'], 'cuentas', AccountsController::class)->name('accounts');
Route::match(['get', 'post'], 'roles', RolesController::class)->name('roles');
Route::get('auditoria', AuditController::class)->name('audit');
Route::get('respaldos', [BackupController::class, 'index'])->name('backups');
Route::post('respaldos', [BackupController::class, 'action']);
Route::post('respaldos/sql', [BackupController::class, 'sql'])->name('backups.sql');

/*
|--------------------------------------------------------------------------
| Direcciones de la versión anterior
|--------------------------------------------------------------------------
| Enlaces guardados o compartidos con .php siguen funcionando.
*/

$legacy = [
    'install.php' => '/instalar',
    'accounts.php' => '/cuentas',
    'roles.php' => '/roles',
    'auditoria.php' => '/auditoria',
    'respaldos.php' => '/respaldos',
];
foreach (array_keys(PortalPageController::SERVICES) as $service) {
    $legacy[$service.'.php'] = '/'.$service;
}
foreach ($legacy as $from => $to) {
    Route::permanentRedirect($from, $to);
}
