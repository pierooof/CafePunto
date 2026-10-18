<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Models\StampLog;
use App\Models\UserRepository;
use App\Services\LoyaltyException;
use App\Services\LoyaltyService;
use App\Services\Migrator;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Test de integración contra MySQL real. Se salta si no hay TEST_MYSQL_URL.
 * ¡Borra y recrea las tablas de esa base de datos! Usa una base desechable.
 */
final class LoyaltyServiceTest extends TestCase
{
    private PDO $pdo;
    private LoyaltyService $loyalty;
    private UserRepository $users;
    private int $barista;
    private int $customer;

    protected function setUp(): void
    {
        $url = getenv('TEST_MYSQL_URL') ?: ($_ENV['TEST_MYSQL_URL'] ?? '');
        if ($url === '') {
            $this->markTestSkipped('Define TEST_MYSQL_URL para ejecutar los tests con MySQL.');
        }
        $s = Database::settings($url);
        $this->pdo = Database::open($s['dsn'], $s['user'], $s['password']);
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['stamp_log', 'users', 'schema_migrations'] as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS $table");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        (new Migrator($this->pdo, dirname(__DIR__) . '/db/migrations'))->run();

        $this->users = new UserRepository($this->pdo);
        $this->loyalty = new LoyaltyService($this->pdo);
        $this->barista = $this->users->create('Barista', '600000001', 'x', 'barista');
        $this->customer = $this->users->create('Cliente', '600000002', 'x', 'customer');
    }

    public function testMigrationsAreIdempotent(): void
    {
        $this->assertSame([], (new Migrator($this->pdo, dirname(__DIR__) . '/db/migrations'))->run());
    }

    public function testStampsAccumulateAndAreLogged(): void
    {
        $this->assertSame(1, $this->loyalty->addStamps($this->customer, $this->barista, 1));
        $this->assertSame(3, $this->loyalty->addStamps($this->customer, $this->barista, 2));

        $this->assertSame(3, (int) $this->users->find($this->customer)['stamps']);
        $history = (new StampLog($this->pdo))->history($this->customer);
        $this->assertCount(2, $history);
        $this->assertSame('Barista', $history[0]['staff_name']);
    }

    public function testCannotExceedGoalAndFailedAddLeavesNoTrace(): void
    {
        $this->loyalty->addStamps($this->customer, $this->barista, 9);
        try {
            $this->loyalty->addStamps($this->customer, $this->barista, 2);
            $this->fail('Debió rechazar el exceso de sellos');
        } catch (LoyaltyException) {
        }
        $this->assertSame(9, (int) $this->users->find($this->customer)['stamps']);
        $this->assertCount(1, (new StampLog($this->pdo))->history($this->customer));
    }

    public function testRedeemOnlyWithFullCardAndResetsBalance(): void
    {
        $this->loyalty->addStamps($this->customer, $this->barista, 5);
        try {
            $this->loyalty->redeem($this->customer, $this->barista);
            $this->fail('Debió rechazar el canje con la tarjeta incompleta');
        } catch (LoyaltyException) {
        }

        $this->loyalty->addStamps($this->customer, $this->barista, 5);
        $this->loyalty->redeem($this->customer, $this->barista);
        $this->assertSame(0, (int) $this->users->find($this->customer)['stamps']);
    }

    public function testStatsAndRankingAreComputedFromLog(): void
    {
        $other = $this->users->create('Otro', '600000003', 'x', 'customer');
        $now = new DateTimeImmutable('2026-10-18 12:00:00');

        $this->loyalty->addStamps($this->customer, $this->barista, 10, $now->modify('-40 days'));
        $this->loyalty->redeem($this->customer, $this->barista, $now->modify('-39 days'));
        $this->loyalty->addStamps($this->customer, $this->barista, 2, $now->modify('-1 hour'));
        $this->loyalty->addStamps($other, $this->barista, 3, $now->modify('-2 hours'));
        $this->loyalty->addStamps($other, $this->barista, 1, $now->modify('-5 days'));

        $log = new StampLog($this->pdo);
        $this->assertSame(5, $log->stampsSince($now->setTime(0, 0)));
        $this->assertSame(0, $log->redeemsSince($now->modify('first day of this month')->setTime(0, 0)));
        $this->assertSame(1, $log->redeemsSince($now->modify('-60 days')));

        $top = $log->topCustomers(5);
        $this->assertSame('Cliente', $top[0]['name']);
        $this->assertSame(12, (int) $top[0]['total_stamps']);
        $this->assertSame(1, (int) $top[0]['redeems']);
    }

    public function testSearchByPhoneAndName(): void
    {
        $this->users->create('Álvaro Ruiz', '611222333', 'x', 'customer');
        $this->users->create('Marta Ruiz', '622333444', 'x', 'customer');

        $this->assertCount(1, $this->users->searchCustomers('222 333'));
        $this->assertCount(2, $this->users->searchCustomers('ruiz'));
        $this->assertCount(0, $this->users->searchCustomers('%'));
        // Los baristas nunca salen en la búsqueda de clientes.
        $this->assertCount(0, $this->users->searchCustomers('600000001'));
    }
}
