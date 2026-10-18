<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * Suma sellos y canjea bebidas. El saldo vive en users.stamps y cada
 * movimiento queda en stamp_log, todo dentro de una transacción con el
 * cliente bloqueado para que dos baristas no se pisen.
 */
final class LoyaltyService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return int nuevo saldo de sellos */
    public function addStamps(int $customerId, int $staffId, int $amount, ?DateTimeImmutable $at = null): int
    {
        return $this->apply($customerId, $staffId, 'stamp', $amount, $at);
    }

    public function redeem(int $customerId, int $staffId, ?DateTimeImmutable $at = null): void
    {
        $this->apply($customerId, $staffId, 'redeem', StampRules::GOAL, $at);
    }

    private function apply(int $customerId, int $staffId, string $type, int $amount, ?DateTimeImmutable $at): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT stamps FROM users WHERE id = ? AND role = 'customer' FOR UPDATE");
            $stmt->execute([$customerId]);
            $current = $stmt->fetchColumn();
            if ($current === false) {
                throw new LoyaltyException('Cliente no encontrado.');
            }

            $balance = $type === 'stamp'
                ? StampRules::add((int) $current, $amount)
                : StampRules::redeem((int) $current);

            $this->pdo->prepare('UPDATE users SET stamps = ? WHERE id = ?')->execute([$balance, $customerId]);
            $this->pdo->prepare(
                'INSERT INTO stamp_log (customer_id, staff_id, type, amount, created_at) VALUES (?, ?, ?, ?, ?)'
            )->execute([$customerId, $staffId, $type, $amount, ($at ?? Config::now())->format('Y-m-d H:i:s')]);

            $this->pdo->commit();
            return $balance;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
