<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Services\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base de los tests de integración contra MySQL real. Se salta si no hay TEST_MYSQL_URL.
 * ¡Borra y recrea las tablas de esa base de datos! Usa una base desechable.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $url = getenv('TEST_MYSQL_URL') ?: ($_ENV['TEST_MYSQL_URL'] ?? '');
        if ($url === '') {
            $this->markTestSkipped('Define TEST_MYSQL_URL para ejecutar los tests con MySQL.');
        }
        $s = Database::settings($url);
        $this->pdo = Database::open($s['dsn'], $s['user'], $s['password']);
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['stamp_log', 'login_attempts', 'users', 'schema_migrations'] as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS $table");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        (new Migrator($this->pdo, dirname(__DIR__) . '/db/migrations'))->run();
    }
}
