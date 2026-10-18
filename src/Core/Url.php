<?php

declare(strict_types=1);

namespace App\Core;

final class Url
{
    /** URL absoluta construida siempre desde APP_URL, nunca desde la cabecera Host. */
    public static function to(string $path = '/'): string
    {
        return Config::appUrl() . '/' . ltrim($path, '/');
    }
}
