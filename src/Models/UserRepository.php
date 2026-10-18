<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Phone;
use DateTimeImmutable;
use PDO;

/** Acceso a la tabla users. Todas las consultas van parametrizadas. */
final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public function findByPhone(string $phone): ?array
    {
        return $this->one('SELECT * FROM users WHERE phone = ?', [$phone]);
    }

    /** @return array<string, mixed>|null */
    public function findByToken(string $token): ?array
    {
        return $this->one('SELECT * FROM users WHERE qr_token = ?', [$token]);
    }

    public function create(string $name, string $phone, string $passwordHash, string $role, ?DateTimeImmutable $at = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, phone, password_hash, role, qr_token, created_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            $phone,
            $passwordHash,
            $role,
            bin2hex(random_bytes(16)),
            ($at ?? Config::now())->format('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Busca clientes por teléfono (parcial, desde 3 dígitos) o por nombre.
     *
     * @return list<array<string, mixed>>
     */
    public function searchCustomers(string $query, int $limit = 20): array
    {
        $digits = Phone::digits($query);
        $isPhone = strlen($digits) >= 3 && strlen($digits) >= strlen($query) - 4;

        if ($isPhone) {
            $sql = "SELECT * FROM users WHERE role = 'customer' AND phone LIKE ? ORDER BY name LIMIT $limit";
            $param = '%' . $digits . '%';
        } else {
            // Escapa los comodines de LIKE para que "%" o "_" se busquen literalmente.
            $escaped = addcslashes($query, '%_\\');
            $sql = "SELECT * FROM users WHERE role = 'customer' AND name LIKE ? ORDER BY name LIMIT $limit";
            $param = '%' . $escaped . '%';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$param]);
        return $stmt->fetchAll();
    }

    /**
     * @param list<mixed> $params
     * @return array<string, mixed>|null
     */
    private function one(string $sql, array $params): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }
}
