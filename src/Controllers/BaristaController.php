<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\StampLog;
use App\Models\UserRepository;
use App\Services\LoyaltyException;
use App\Services\LoyaltyService;
use App\Services\StampRules;

final class BaristaController
{
    /** Panel: búsqueda, escáner QR y estadísticas. */
    public function dashboard(Request $request, array $params): Response
    {
        $log = new StampLog(Database::connect());
        $now = Config::now();

        return View::page('barista/dashboard', [
            'title' => 'Panel',
            'scripts' => ['scan.js'],
            'stampsToday' => $log->stampsSince($now->setTime(0, 0)),
            'redeemsMonth' => $log->redeemsSince($now->modify('first day of this month')->setTime(0, 0)),
            'customers' => $log->customerCount(),
            'top' => $log->topCustomers(5),
        ]);
    }

    public function search(Request $request, array $params): Response
    {
        $query = mb_substr($request->input('q'), 0, 60);
        $results = mb_strlen($query) >= 2
            ? (new UserRepository(Database::connect()))->searchCustomers($query)
            : [];

        if (count($results) === 1) {
            return Response::redirect('/barista/customers/' . $results[0]['id']);
        }
        return View::page('barista/search', [
            'title' => 'Buscar cliente',
            'query' => $query,
            'results' => $results,
        ]);
    }

    public function show(Request $request, array $params): Response
    {
        $customer = $this->customer($params);
        if ($customer === null) {
            return View::error(404, 'Cliente no encontrado', 'No existe ningún cliente con ese identificador.');
        }
        return View::page('barista/customer', [
            'title' => $customer['name'],
            'customer' => $customer,
            'goal' => StampRules::GOAL,
            'history' => (new StampLog(Database::connect()))->history((int) $customer['id']),
        ]);
    }

    public function stamp(Request $request, array $params): Response
    {
        $customer = $this->customer($params);
        if ($customer === null) {
            return View::error(404, 'Cliente no encontrado', 'No existe ningún cliente con ese identificador.');
        }

        try {
            $amount = (int) $request->input('amount');
            $balance = (new LoyaltyService(Database::connect()))->addStamps((int) $customer['id'], (int) Auth::user()['id'], $amount);
            Session::flash('success', sprintf('%s lleva %d de %d sellos.', $customer['name'], $balance, StampRules::GOAL));
        } catch (LoyaltyException $e) {
            Session::flash('error', $e->getMessage());
        }
        return Response::redirect('/barista/customers/' . $customer['id']);
    }

    public function redeem(Request $request, array $params): Response
    {
        $customer = $this->customer($params);
        if ($customer === null) {
            return View::error(404, 'Cliente no encontrado', 'No existe ningún cliente con ese identificador.');
        }

        try {
            (new LoyaltyService(Database::connect()))->redeem((int) $customer['id'], (int) Auth::user()['id']);
            Session::flash('success', 'Bebida gratis canjeada. ¡Que la disfrute!');
        } catch (LoyaltyException $e) {
            Session::flash('error', $e->getMessage());
        }
        return Response::redirect('/barista/customers/' . $customer['id']);
    }

    /** @param array<string, string> $params @return array<string, mixed>|null */
    private function customer(array $params): ?array
    {
        $user = (new UserRepository(Database::connect()))->find((int) $params['id']);
        return $user !== null && $user['role'] === 'customer' ? $user : null;
    }
}
