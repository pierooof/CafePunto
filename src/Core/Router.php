<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Router mínimo: rutas con parámetros {nombre} y un "rol" requerido por ruta.
 *
 * Roles: null = pública, 'auth' = cualquier usuario con sesión, 'barista',
 * 'customer', 'open' = sin sesión ni redirección a HTTPS (healthcheck).
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: array{class-string, string}, role: ?string}> */
    private array $routes = [];

    /** @param array{class-string, string} $handler [Controlador::class, 'metodo'] */
    public function get(string $path, array $handler, ?string $role = null): void
    {
        $this->add('GET', $path, $handler, $role);
    }

    /** @param array{class-string, string} $handler */
    public function post(string $path, array $handler, ?string $role = null): void
    {
        $this->add('POST', $path, $handler, $role);
    }

    /** @param array{class-string, string} $handler */
    private function add(string $method, string $path, array $handler, ?string $role): void
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?<$1>[^/]+)', $path);
        $this->routes[] = ['method' => $method, 'regex' => '#^' . $regex . '$#', 'handler' => $handler, 'role' => $role];
    }

    /** @return array{handler: array{class-string, string}, role: ?string, params: array<string, string>}|null */
    public function match(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $path, $m) === 1) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                return ['handler' => $route['handler'], 'role' => $route['role'], 'params' => $params];
            }
        }
        return null;
    }
}
