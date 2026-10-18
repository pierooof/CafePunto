<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/**
 * Acceso a la configuración. Todo viene de variables de entorno; en local
 * se puede cargar un fichero .env que nunca pisa una variable ya definida.
 */
final class Config
{
    /** @var array<string, string> valores del .env o fijados en tests */
    private static array $values = [];

    public static function load(string $file): void
    {
        if (!is_readable($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if (getenv($key) === false) {
                self::$values[$key] = trim($value, "\"'");
            }
        }
    }

    /** Solo para tests: fija (o con null elimina) un valor. */
    public static function set(string $key, ?string $value): void
    {
        if ($value === null) {
            unset(self::$values[$key]);
        } else {
            self::$values[$key] = $value;
        }
    }

    /** Una variable vacía cuenta como no definida (así lo hace Railway con las opcionales). */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::$values[$key] ?? getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'production') === 'production';
    }

    /** URL pública sin barra final. Única fuente para QR y redirecciones. */
    public static function appUrl(): string
    {
        $url = self::get('APP_URL');
        if ($url === null) {
            if (self::isProduction()) {
                throw new RuntimeException('APP_URL es obligatoria en producción.');
            }
            $url = 'http://localhost:' . (self::get('PORT') ?? '8000');
        }
        if (self::isProduction() && !str_starts_with($url, 'https://')) {
            throw new RuntimeException('APP_URL debe empezar por https:// en producción.');
        }
        return rtrim($url, '/');
    }

    public static function trustedProxies(): int
    {
        return max(0, (int) self::get('TRUSTED_PROXIES', '1'));
    }

    public static function timezone(): string
    {
        return self::get('APP_TIMEZONE', 'Europe/Madrid');
    }

    /** "Ahora" en la zona horaria de la app: todas las fechas se guardan en ella. */
    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::timezone()));
    }

    public static function flag(string $key): bool
    {
        return self::get($key) === 'true';
    }
}
