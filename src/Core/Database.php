<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            ['dsn' => $dsn, 'user' => $user, 'password' => $password] = self::settings();
            self::$pdo = self::open($dsn, $user, $password);
        }
        return self::$pdo;
    }

    public static function open(string $dsn, string $user, string $password): PDO
    {
        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * Lee la conexión de MYSQL_URL o, si no existe, de las variables sueltas
     * MYSQLHOST/PORT/USER/PASSWORD/DATABASE que inyecta Railway.
     *
     * @return array{dsn: string, user: string, password: string}
     */
    public static function settings(?string $url = null): array
    {
        $url ??= Config::get('MYSQL_URL');
        if ($url !== null) {
            $p = parse_url($url);
            if ($p === false || !isset($p['host'], $p['path'])) {
                throw new RuntimeException('MYSQL_URL no es válida.');
            }
            $host = $p['host'];
            $port = (int) ($p['port'] ?? 3306);
            $db = ltrim($p['path'], '/');
            $user = urldecode($p['user'] ?? '');
            $password = urldecode($p['pass'] ?? '');
        } else {
            $host = Config::get('MYSQLHOST');
            $db = Config::get('MYSQLDATABASE');
            if ($host === null || $db === null) {
                throw new RuntimeException('Falta la configuración de MySQL (MYSQL_URL o MYSQLHOST y MYSQLDATABASE).');
            }
            $port = (int) Config::get('MYSQLPORT', '3306');
            $user = Config::get('MYSQLUSER', 'root');
            $password = Config::get('MYSQLPASSWORD', '');
        }

        return [
            'dsn' => "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
            'user' => $user,
            'password' => $password,
        ];
    }
}
