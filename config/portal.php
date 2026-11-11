<?php

/*
|--------------------------------------------------------------------------
| Portal institucional
|--------------------------------------------------------------------------
|
| Ajustes que recibe el núcleo del portal (carpeta portal/). Se toman del .env.
| La conexión MySQL es la misma de config/database.php (DB_HOST, DB_DATABASE...).
|
| PORTAL_CONFIG_FILE permite, opcionalmente, reutilizar un config.php de la
| versión anterior (sin Laravel): sus valores tienen prioridad sobre el .env.
| Las pruebas de integración también lo usan para apuntar a bases *_test.
|
*/

$settings = [
    'db_host' => env('DB_HOST', '127.0.0.1'),
    'db_port' => (int) env('DB_PORT', 3306),
    'db_name' => env('DB_DATABASE', ''),
    'db_user' => env('DB_USERNAME', ''),
    'db_password' => env('DB_PASSWORD', ''),

    // Clave privada aleatoria de 32 caracteres o más. Se pide en /instalar y cifra los ajustes SMTP guardados.
    'install_key' => env('PORTAL_INSTALL_KEY', ''),

    'timezone' => env('APP_TIMEZONE', 'America/Lima'),

    // Meses de auditoría que se conservan (mínimo 6). 0 la conserva sin límite.
    'audit_retention_months' => (int) env('PORTAL_AUDIT_RETENTION_MONTHS', 24),

    // Alternativa al formulario Configuración → Configurar correo notificador (SMTP).
    // El formulario guarda ajustes cifrados que tienen prioridad sobre este bloque.
    'smtp' => [
        'enabled' => (bool) env('PORTAL_SMTP_ENABLED', false),
        'host' => env('PORTAL_SMTP_HOST', 'smtp.gmail.com'),
        'port' => (int) env('PORTAL_SMTP_PORT', 587),
        'encryption' => env('PORTAL_SMTP_ENCRYPTION', 'tls'),
        'username' => env('PORTAL_SMTP_USERNAME', 'notificaciones@instituto.edu.pe'),
        'password' => env('PORTAL_SMTP_PASSWORD', ''),
        'from_email' => env('PORTAL_SMTP_FROM_EMAIL', 'notificaciones@instituto.edu.pe'),
        'from_name' => env('PORTAL_SMTP_FROM_NAME', 'Notificaciones · IESTP Chincha'),
    ],
];

$legacy = env('PORTAL_CONFIG_FILE');
if (is_string($legacy) && $legacy !== '' && is_file($legacy)) {
    $values = require $legacy;
    if (is_array($values)) {
        $settings = array_replace($settings, $values);
    }
}

return $settings;
