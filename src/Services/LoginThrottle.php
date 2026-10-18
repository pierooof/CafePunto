<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use DateTimeImmutable;
use PDO;

/**
 * Limita los intentos fallidos de login: por teléfono (frena el acierto de una
 * contraseña) y por IP (frena el barrido de muchos teléfonos).
 *
 * ponytail: un teléfono bloqueado también bloquea al dueño legítimo hasta que
 * expire la ventana. Para evitarlo, bloquear por pareja IP+teléfono.
 */
final class LoginThrottle
{
    public const MAX_PER_PHONE = 5;
    public const MAX_PER_IP = 20;
    public const WINDOW_SECONDS = 900; // 15 minutos

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Segundos que faltan para poder reintentar; 0 si no hay bloqueo. */
    public function retryAfter(string $ip, string $phone, ?DateTimeImmutable $now = null): int
    {
        $now ??= Config::now();
        $since = $now->modify('-' . self::WINDOW_SECONDS . ' seconds')->format('Y-m-d H:i:s');

        $wait = 0;
        foreach (['phone' => [$phone, self::MAX_PER_PHONE], 'ip' => [$ip, self::MAX_PER_IP]] as $column => [$value, $max]) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*), MIN(created_at) FROM login_attempts WHERE $column = ? AND created_at > ?");
            $stmt->execute([$value, $since]);
            [$count, $oldest] = $stmt->fetch(PDO::FETCH_NUM);
            if ((int) $count >= $max) {
                // Se libera cuando caduque el fallo más antiguo de la ventana.
                $free = (new DateTimeImmutable((string) $oldest, $now->getTimezone()))->getTimestamp() + self::WINDOW_SECONDS;
                $wait = max($wait, $free - $now->getTimestamp());
            }
        }
        return max(0, $wait);
    }

    public function recordFailure(string $ip, string $phone, ?DateTimeImmutable $now = null): void
    {
        $now ??= Config::now();
        $this->pdo->prepare('INSERT INTO login_attempts (ip, phone, created_at) VALUES (?, ?, ?)')
            ->execute([$ip, $phone, $now->format('Y-m-d H:i:s')]);
        // Limpieza oportunista de lo que ya no cuenta.
        $this->pdo->prepare('DELETE FROM login_attempts WHERE created_at < ?')
            ->execute([$now->modify('-' . self::WINDOW_SECONDS . ' seconds')->format('Y-m-d H:i:s')]);
    }

    /** Un login correcto borra los fallos previos de ese teléfono. */
    public function clear(string $phone): void
    {
        $this->pdo->prepare('DELETE FROM login_attempts WHERE phone = ?')->execute([$phone]);
    }
}
