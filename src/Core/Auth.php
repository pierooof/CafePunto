<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\UserRepository;

/** Usuario autenticado de la petición actual. */
final class Auth
{
    /** @var array<string, mixed>|null */
    private static ?array $user = null;
    private static bool $loaded = false;

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::userId();
            self::$user = $id === null ? null : (new UserRepository(Database::connect()))->find($id);
        }
        return self::$user;
    }

    /** Comprueba teléfono y contraseña; devuelve el usuario o null. */
    public static function attempt(string $phone, string $password): ?array
    {
        $user = (new UserRepository(Database::connect()))->findByPhone($phone);
        // Se verifica siempre contra un hash para que el tiempo no delate si el teléfono existe.
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsalt.u0gUq8f9fbdM6Yv7nHcqg5ZKq9iKe';
        return password_verify($password, $hash) && $user !== null ? $user : null;
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user): void
    {
        Session::login((int) $user['id']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::logout();
        self::$user = null;
    }

    /** Página de inicio según el rol. @param array<string, mixed> $user */
    public static function home(array $user): string
    {
        return $user['role'] === 'barista' ? '/barista' : '/card';
    }
}
