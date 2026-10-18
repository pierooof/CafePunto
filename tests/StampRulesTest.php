<?php

declare(strict_types=1);

namespace Tests;

use App\Services\LoyaltyException;
use App\Services\StampRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StampRulesTest extends TestCase
{
    public function testAddingStampsIncreasesBalance(): void
    {
        $this->assertSame(1, StampRules::add(0, 1));
        $this->assertSame(10, StampRules::add(7, 3));
    }

    /** @return array<string, array{int, int}> */
    public static function invalidAdds(): array
    {
        return [
            'cero sellos' => [3, 0],
            'negativo' => [3, -1],
            'más que la meta' => [0, 11],
            'se pasa de la meta' => [8, 3],
            'tarjeta ya completa' => [10, 1],
        ];
    }

    #[DataProvider('invalidAdds')]
    public function testInvalidAddsAreRejected(int $current, int $amount): void
    {
        $this->expectException(LoyaltyException::class);
        StampRules::add($current, $amount);
    }

    public function testRedeemResetsCardOnlyWhenComplete(): void
    {
        $this->assertSame(0, StampRules::redeem(10));

        $this->expectException(LoyaltyException::class);
        $this->expectExceptionMessage('faltan 3');
        StampRules::redeem(7);
    }
}
