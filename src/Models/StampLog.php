<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use PDO;

/** Consultas de lectura sobre el historial (stamp_log): historial y estadísticas. */
final class StampLog
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function history(int $customerId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT l.type, l.amount, l.created_at, s.name AS staff_name
             FROM stamp_log l LEFT JOIN users s ON s.id = l.staff_id
             WHERE l.customer_id = ?
             ORDER BY l.created_at DESC, l.id DESC LIMIT $limit"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    /** Sellos entregados desde $since. */
    public function stampsSince(DateTimeImmutable $since): int
    {
        return (int) $this->scalar(
            "SELECT COALESCE(SUM(amount), 0) FROM stamp_log WHERE type = 'stamp' AND created_at >= ?",
            [$since->format('Y-m-d H:i:s')]
        );
    }

    /** Bebidas canjeadas desde $since. */
    public function redeemsSince(DateTimeImmutable $since): int
    {
        return (int) $this->scalar(
            "SELECT COUNT(*) FROM stamp_log WHERE type = 'redeem' AND created_at >= ?",
            [$since->format('Y-m-d H:i:s')]
        );
    }

    public function customerCount(): int
    {
        return (int) $this->scalar("SELECT COUNT(*) FROM users WHERE role = 'customer'", []);
    }

    /**
     * Clientes más fieles: los que más sellos han acumulado en total.
     *
     * @return list<array{id: int, name: string, total_stamps: int, redeems: int}>
     */
    public function topCustomers(int $limit = 5): array
    {
        return $this->pdo->query(
            "SELECT u.id, u.name,
                    COALESCE(SUM(CASE WHEN l.type = 'stamp' THEN l.amount END), 0) AS total_stamps,
                    COALESCE(SUM(l.type = 'redeem'), 0) AS redeems
             FROM users u JOIN stamp_log l ON l.customer_id = u.id
             WHERE u.role = 'customer'
             GROUP BY u.id, u.name
             ORDER BY total_stamps DESC, u.name
             LIMIT $limit"
        )->fetchAll();
    }

    /** @param list<mixed> $params */
    private function scalar(string $sql, array $params): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
