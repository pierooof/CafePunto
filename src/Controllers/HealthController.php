<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use Throwable;

final class HealthController
{
    /** Healthcheck de Railway: 200 si la app responde y MySQL contesta, 503 si no. */
    public function show(Request $request, array $params): Response
    {
        try {
            Database::connect()->query('SELECT 1');
            return Response::json(['status' => 'ok']);
        } catch (Throwable $e) {
            error_log('[cafepunto] healthcheck: ' . $e->getMessage());
            return Response::json(['status' => 'error'], 503);
        }
    }
}
