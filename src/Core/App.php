<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/** Ciclo de una petición: HTTPS, sesión, ruta, CSRF, permisos y controlador. */
final class App
{
    public function __construct(private readonly Router $router)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->dispatch($request);
        } catch (Throwable $e) {
            error_log(sprintf('[cafepunto] %s: %s en %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
            $response = View::error(500, 'Algo ha salido mal', 'Ha ocurrido un error inesperado. Inténtalo de nuevo en unos minutos.');
        }
        return $response->withHeaders(self::securityHeaders($request));
    }

    private function dispatch(Request $request): Response
    {
        $route = $this->router->match($request->method, $request->path);
        if ($route === null) {
            Session::start();
            return View::error(404, 'Página no encontrada', 'La página que buscas no existe o se ha movido.');
        }

        $handler = fn (): Response => (new $route['handler'][0])->{$route['handler'][1]}($request, $route['params']);
        if ($route['role'] === 'open') {
            return $handler(); // /health: sin sesión ni redirección
        }

        // En producción todo va por HTTPS (la detección respeta X-Forwarded-Proto).
        if (Config::isProduction() && !$request->isHttps()) {
            $query = $request->query === [] ? '' : '?' . http_build_query($request->query);
            return Response::redirect($request->path . $query, 301);
        }

        Session::start();

        if ($request->method === 'POST' && !Csrf::verify($request->post['_csrf'] ?? null)) {
            return View::error(419, 'Sesión caducada', 'El formulario ha caducado. Vuelve atrás, recarga la página e inténtalo de nuevo.');
        }

        return $this->authorize($request, $route['role']) ?? $handler();
    }

    /** Devuelve una respuesta si el usuario no puede acceder a la ruta; null si puede. */
    private function authorize(Request $request, ?string $role): ?Response
    {
        if ($role === null) {
            return null;
        }

        $user = Auth::user();
        if ($user === null) {
            // Se recuerda la ruta para volver tras iniciar sesión (p. ej. al escanear un QR).
            if ($request->method === 'GET') {
                $_SESSION['intended'] = $request->path;
            }
            Session::flash('info', 'Inicia sesión para continuar.');
            return Response::redirect('/login');
        }

        // 'admin' = barista con permiso para gestionar el equipo.
        if ($role === 'admin') {
            return $user['role'] === 'barista' && (int) $user['is_admin'] === 1 ? null : Response::redirect(Auth::home($user));
        }
        if ($role !== 'auth' && $user['role'] !== $role) {
            return Response::redirect(Auth::home($user));
        }
        return null;
    }

    /** @return array<string, string> */
    private static function securityHeaders(Request $request): array
    {
        $headers = [
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; "
                . "style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; "
                . "img-src 'self' data:; media-src 'self' blob:; connect-src 'self'; "
                . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=()',
            'Cache-Control' => 'no-store',
        ];
        if (Config::isProduction() && $request->isHttps()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }
        return $headers;
    }
}
