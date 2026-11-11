<?php

namespace App\Support;

/** URL de un recurso estático con versión (fecha de modificación) para que el navegador lo guarde en caché sin quedar desactualizado. */
final class Asset
{
    public static function url(string $file): string
    {
        $url = str_starts_with($file, '/') ? $file : '/assets/app/'.$file;
        $path = public_path(ltrim($url, '/'));

        return $url.'?v='.(is_file($path) ? filemtime($path) : '0');
    }
}
