<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private const LIFETIME = 60 * 60 * 24 * 7;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.gc_maxlifetime', (string) self::LIFETIME);
        session_name('cafepunto_sid');
        session_set_cookie_params([
            'lifetime' => self::LIFETIME,
            'path' => '/',
            'secure' => Config::isProduction(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        // ponytail: sesiones en ficheros del contenedor; se pierden al redesplegar.
        // Para persistirlas, un handler en MySQL.
        session_start();
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /** Inicia sesión regenerando el id (evita fijación de sesión). */
    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        // Borra la cookie con los mismos atributos con los que se creó.
        setcookie(session_name(), '', [
            'expires' => 1,
            'path' => '/',
            'secure' => Config::isProduction(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return list<array{type: string, message: string}> */
    public static function pullFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}
