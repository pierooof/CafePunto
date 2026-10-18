<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $post = [],
        public readonly array $server = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $path = '/' . trim($path, '/');

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET,
            $_POST,
            $_SERVER,
        );
    }

    /** Valor de texto de POST (o GET) recortado; cadena vacía si falta o no es texto. */
    public function input(string $key): string
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    public function isHttps(): bool
    {
        return Proxy::isHttps($this->server, Config::trustedProxies());
    }

    public function ip(): string
    {
        return Proxy::clientIp($this->server, Config::trustedProxies());
    }
}
