<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Url;
use App\Core\View;
use App\Models\StampLog;
use App\Models\UserRepository;
use App\Services\QrService;
use App\Services\StampRules;

final class CustomerController
{
    /** Tarjeta del cliente: sellos, QR personal e historial. */
    public function card(Request $request, array $params): Response
    {
        $user = Auth::user();
        return View::page('customer/card', [
            'title' => 'Mi tarjeta',
            'customer' => $user,
            'goal' => StampRules::GOAL,
            'qr' => QrService::svgDataUri(Url::to('/c/' . $user['qr_token'])),
            'history' => (new StampLog(Database::connect()))->history((int) $user['id']),
        ]);
    }

    /**
     * Destino del QR (APP_URL/c/{token}). Si lo abre un barista va a la ficha
     * del cliente; si lo abre el propio cliente, a su tarjeta.
     */
    public function scan(Request $request, array $params): Response
    {
        $user = Auth::user();
        if ($user['role'] !== 'barista') {
            return Response::redirect('/card');
        }
        $customer = (new UserRepository(Database::connect()))->findByToken($params['token']);
        if ($customer === null || $customer['role'] !== 'customer') {
            return View::error(404, 'QR no válido', 'No encontramos ningún cliente con ese código.');
        }
        return Response::redirect('/barista/customers/' . $customer['id']);
    }
}
