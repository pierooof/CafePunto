<?php

declare(strict_types=1);

namespace App\Services;

/** Reglas puras de la tarjeta (sin base de datos, fáciles de testear). */
final class StampRules
{
    /** Sellos necesarios para una bebida gratis. */
    public const GOAL = 10;

    /** Devuelve el nuevo saldo tras sumar $amount sellos. */
    public static function add(int $current, int $amount): int
    {
        if ($amount < 1 || $amount > self::GOAL) {
            throw new LoyaltyException('Cantidad de sellos no válida.');
        }
        if ($current >= self::GOAL) {
            throw new LoyaltyException('La tarjeta está completa: toca canjear la bebida gratis.');
        }
        if ($current + $amount > self::GOAL) {
            throw new LoyaltyException('Solo ' . self::missing(self::GOAL - $current) . ' para completar la tarjeta.');
        }
        return $current + $amount;
    }

    /** Devuelve el nuevo saldo tras canjear (siempre 0). */
    public static function redeem(int $current): int
    {
        if ($current < self::GOAL) {
            throw new LoyaltyException('Aún ' . self::missing(self::GOAL - $current) . ' para poder canjear.');
        }
        return $current - self::GOAL;
    }

    /** "falta 1 sello" / "faltan 3 sellos" */
    private static function missing(int $n): string
    {
        return $n === 1 ? 'falta 1 sello' : "faltan $n sellos";
    }
}
