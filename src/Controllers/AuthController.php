<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Phone;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\UserRepository;
use App\Services\LoginThrottle;
use PDOException;

final class AuthController
{
    public function home(Request $request, array $params): Response
    {
        $user = Auth::user();
        return $user !== null ? Response::redirect(Auth::home($user)) : View::page('home', ['title' => 'Tu tarjeta de sellos']);
    }

    public function showLogin(Request $request, array $params): Response
    {
        $user = Auth::user();
        return $user !== null ? Response::redirect(Auth::home($user)) : $this->loginPage();
    }

    public function login(Request $request, array $params): Response
    {
        $phone = Phone::normalize($request->input('phone'));
        $key = $phone ?? 'invalid';
        $throttle = new LoginThrottle(Database::connect());

        // Con el límite superado ni siquiera se comprueba la contraseña.
        $wait = $throttle->retryAfter($request->ip(), $key);
        if ($wait > 0) {
            $minutes = (int) ceil($wait / 60);
            return $this->loginPage(
                'Demasiados intentos fallidos. Vuelve a intentarlo en ' . $minutes . ($minutes === 1 ? ' minuto.' : ' minutos.'),
                $request->input('phone'),
                429,
            )->withHeaders(['Retry-After' => (string) $wait]);
        }

        $user = $phone === null ? null : Auth::attempt($phone, (string) ($request->post['password'] ?? ''));
        if ($user === null) {
            $throttle->recordFailure($request->ip(), $key);
            return $this->loginPage('Teléfono o contraseña incorrectos.', $request->input('phone'));
        }

        $throttle->clear($key);
        Auth::login($user);
        $intended = (string) ($_SESSION['intended'] ?? '');
        unset($_SESSION['intended']);
        // Solo rutas internas: evita redirecciones abiertas.
        $isLocal = str_starts_with($intended, '/') && !str_starts_with($intended, '//') && !str_contains($intended, '\\');
        return Response::redirect($isLocal ? $intended : Auth::home($user));
    }

    public function showRegister(Request $request, array $params): Response
    {
        $user = Auth::user();
        return $user !== null ? Response::redirect(Auth::home($user)) : View::page('auth/register', ['title' => 'Crear cuenta', 'errors' => [], 'old' => []]);
    }

    public function register(Request $request, array $params): Response
    {
        $name = preg_replace('/\s+/', ' ', $request->input('name')) ?? '';
        $phone = Phone::normalize($request->input('phone'));
        $password = (string) ($request->post['password'] ?? '');
        $users = new UserRepository(Database::connect());

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
            $errors['name'] = 'Escribe tu nombre (entre 2 y 60 caracteres).';
        }
        if ($phone === null) {
            $errors['phone'] = 'Introduce un teléfono válido (9 a 15 dígitos).';
        } elseif ($users->findByPhone($phone) !== null) {
            $errors['phone'] = 'Ya existe una cuenta con ese teléfono.';
        }
        // bcrypt solo usa los primeros 72 bytes.
        if (mb_strlen($password) < 8 || strlen($password) > 72) {
            $errors['password'] = 'La contraseña debe tener entre 8 y 72 caracteres.';
        }

        if ($errors === []) {
            try {
                $id = $users->create($name, $phone, password_hash($password, PASSWORD_DEFAULT), 'customer');
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') { // 23000: teléfono duplicado por una carrera
                    throw $e;
                }
                $errors['phone'] = 'Ya existe una cuenta con ese teléfono.';
            }
        }
        if ($errors !== []) {
            return View::page('auth/register', [
                'title' => 'Crear cuenta',
                'errors' => $errors,
                'old' => ['name' => $name, 'phone' => $request->input('phone')],
            ], 422);
        }

        Auth::login($users->find($id));
        return Response::redirect('/card');
    }

    public function logout(Request $request, array $params): Response
    {
        Auth::logout();
        return Response::redirect('/login');
    }

    private function loginPage(?string $error = null, string $phone = '', int $status = 422): Response
    {
        return View::page('auth/login', [
            'title' => 'Entrar',
            'error' => $error,
            'phone' => $phone,
            'demoHint' => Config::flag('DEMO_HINT'),
        ], $error === null ? 200 : $status);
    }
}
