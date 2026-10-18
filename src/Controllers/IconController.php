<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

final class IconController
{
    /**
     * Icono de iOS (apple-touch-icon). Se sirve desde PHP, en una ruta sin extensión,
     * para que cada petición llegue a la app y quede registrada (Cloudflare no cachea
     * rutas sin extensión de fichero estático).
     */
    public function touchIcon(Request $request, array $params): Response
    {
        error_log(sprintf(
            '[cafepunto] touch-icon pedido por ip=%s ua="%s" accept="%s"',
            $request->ip(),
            $request->server['HTTP_USER_AGENT'] ?? '',
            $request->server['HTTP_ACCEPT'] ?? ''
        ));

        $png = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/icons/touch-icon-180-v2.png');
        return new Response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => (string) strlen($png),
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
