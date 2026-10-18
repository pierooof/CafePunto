<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Interpreta las cabeceras que añaden los proxies (Cloudflare, Railway).
 * Solo se fía de ellas si hay al menos un proxy de confianza configurado.
 */
final class Proxy
{
    /** @param array<string, mixed> $server $_SERVER */
    public static function isHttps(array $server, int $trusted): bool
    {
        $https = strtolower((string) ($server['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return true;
        }
        if ($trusted <= 0) {
            return false;
        }
        // Con varios proxies la cabecera puede ser "https,http": manda el primero.
        $proto = explode(',', (string) ($server['HTTP_X_FORWARDED_PROTO'] ?? ''))[0];
        return strtolower(trim($proto)) === 'https';
    }

    /** @param array<string, mixed> $server $_SERVER */
    public static function clientIp(array $server, int $trusted): string
    {
        $remote = (string) ($server['REMOTE_ADDR'] ?? '0.0.0.0');
        if ($trusted <= 0) {
            return $remote;
        }

        // Cloudflare siempre reescribe esta cabecera con la IP real.
        $cf = trim((string) ($server['HTTP_CF_CONNECTING_IP'] ?? ''));
        if (filter_var($cf, FILTER_VALIDATE_IP) !== false) {
            return $cf;
        }

        // Cada proxy añade por la derecha la IP que vio, así que la IP real es
        // la que queda $trusted posiciones antes del final de la cadena. Lo que
        // un cliente malicioso escriba a la izquierda no cuenta.
        $chain = array_map('trim', explode(',', (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '')));
        $chain = array_values(array_filter($chain, static fn (string $ip): bool => $ip !== ''));
        $chain[] = $remote;
        $ip = $chain[max(0, count($chain) - 1 - $trusted)];

        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : $remote;
    }
}
