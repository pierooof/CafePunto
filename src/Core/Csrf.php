<?php

declare(strict_types=1);

namespace App\Core;

/** Token CSRF por sesión; se exige en todas las peticiones POST. */
final class Csrf
{
    public static function token(): string
    {
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function verify(mixed $submitted): bool
    {
        $expected = $_SESSION['_csrf'] ?? '';
        return is_string($submitted) && $expected !== '' && hash_equals($expected, $submitted);
    }
}
