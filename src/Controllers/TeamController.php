<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Phone;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\UserRepository;
use PDOException;

/** Gestión del equipo: solo los baristas con is_admin pueden entrar (ver App::authorize). */
final class TeamController
{
    public function index(Request $request, array $params): Response
    {
        return $this->page((new UserRepository(Database::connect()))->baristas());
    }

    public function create(Request $request, array $params): Response
    {
        $users = new UserRepository(Database::connect());
        $name = preg_replace('/\s+/', ' ', $request->input('name')) ?? '';
        $phone = Phone::normalize($request->input('phone'));
        $password = (string) ($request->post['password'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
            $errors['name'] = 'Escribe un nombre (entre 2 y 60 caracteres).';
        }
        if ($phone === null) {
            $errors['phone'] = 'Introduce un teléfono válido (9 a 15 dígitos).';
        } elseif ($users->findByPhone($phone) !== null) {
            $errors['phone'] = 'Ya existe una cuenta con ese teléfono.';
        }
        if (mb_strlen($password) < 8 || strlen($password) > 72) {
            $errors['password'] = 'La contraseña debe tener entre 8 y 72 caracteres.';
        }

        if ($errors === []) {
            try {
                $users->create($name, $phone, password_hash($password, PASSWORD_DEFAULT), 'barista', null, $request->input('is_admin') === '1');
                Session::flash('success', "$name ya forma parte del equipo.");
                return Response::redirect('/barista/team');
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') { // teléfono duplicado por una carrera
                    throw $e;
                }
                $errors['phone'] = 'Ya existe una cuenta con ese teléfono.';
            }
        }

        return $this->page($users->baristas(), $errors, [
            'name' => $name,
            'phone' => $request->input('phone'),
            'is_admin' => $request->input('is_admin') === '1',
        ], 422);
    }

    public function delete(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        if ($id === (int) Auth::user()['id']) {
            Session::flash('error', 'No puedes eliminar tu propia cuenta.');
        } elseif ((new UserRepository(Database::connect()))->deleteBarista($id)) {
            Session::flash('success', 'Barista eliminado.');
        } else {
            Session::flash('error', 'No se encontró ese barista.');
        }
        return Response::redirect('/barista/team');
    }

    /**
     * @param list<array<string, mixed>> $team
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    private function page(array $team, array $errors = [], array $old = [], int $status = 200): Response
    {
        return View::page('barista/team', [
            'title' => 'Equipo',
            'team' => $team,
            'me' => (int) Auth::user()['id'],
            'errors' => $errors,
            'old' => $old,
        ], $status);
    }
}
