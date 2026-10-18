<?php

declare(strict_types=1);

// Aplica las migraciones pendientes. Uso: php bin/migrate.php  (o composer migrate)
// Reintenta la conexión porque en un arranque en frío MySQL puede tardar en estar listo.

use App\Core\Database;
use App\Services\Migrator;

require dirname(__DIR__) . '/src/bootstrap.php';

$settings = Database::settings();
for ($attempt = 1;; $attempt++) {
    try {
        $pdo = Database::open($settings['dsn'], $settings['user'], $settings['password']);
        break;
    } catch (PDOException $e) {
        if ($attempt >= 30) {
            fwrite(STDERR, "No se pudo conectar a MySQL: {$e->getMessage()}\n");
            exit(1);
        }
        fwrite(STDERR, "Esperando a MySQL (intento $attempt/30)...\n");
        sleep(2);
    }
}

$applied = (new Migrator($pdo, dirname(__DIR__) . '/db/migrations'))->run();
echo $applied === [] ? "Base de datos al día.\n" : 'Migraciones aplicadas: ' . implode(', ', $applied) . "\n";
