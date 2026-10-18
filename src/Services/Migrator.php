<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/** Aplica en orden los ficheros db/migrations/*.sql que aún no se hayan ejecutado. */
final class Migrator
{
    public function __construct(private readonly PDO $pdo, private readonly string $dir)
    {
    }

    /** @return list<string> migraciones aplicadas en esta ejecución */
    public function run(): array
    {
        // Evita que dos instancias migren a la vez.
        $this->pdo->query("SELECT GET_LOCK('cafepunto_migrate', 60)")->fetchColumn();
        try {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS schema_migrations (
                    version VARCHAR(100) NOT NULL PRIMARY KEY,
                    applied_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $done = $this->pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

            $applied = [];
            $files = glob($this->dir . '/*.sql') ?: [];
            sort($files);
            foreach ($files as $file) {
                $version = basename($file, '.sql');
                if (in_array($version, $done, true)) {
                    continue;
                }
                // Cada fichero es una sola sentencia DDL (que en MySQL confirma sola).
                $this->pdo->exec((string) file_get_contents($file));
                $this->pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, ?)')
                    ->execute([$version, date('Y-m-d H:i:s')]);
                $applied[] = $version;
            }
            return $applied;
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('cafepunto_migrate')")->fetchColumn();
        }
    }
}
