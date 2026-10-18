<?php

declare(strict_types=1);

// Datos de demostración. Idempotente: si el barista demo ya existe, no hace nada.
// Uso: php bin/seed.php  (o composer seed)

use App\Core\Config;
use App\Core\Database;
use App\Models\UserRepository;
use App\Services\LoyaltyService;
use App\Services\StampRules;

require dirname(__DIR__) . '/src/bootstrap.php';

const DEMO_BARISTA_PHONE = '600000001';
const DEMO_CUSTOMER_PHONE = '600000002';
const DEMO_BARISTA_PASSWORD = 'barista123';
const DEMO_CUSTOMER_PASSWORD = 'cliente123';

$pdo = Database::connect();
$users = new UserRepository($pdo);
$loyalty = new LoyaltyService($pdo);

if ($users->findByPhone(DEMO_BARISTA_PHONE) !== null) {
    echo "Los datos demo ya existen.\n";
    exit(0);
}

mt_srand(2026); // historial reproducible
$now = Config::now();
$pick = static fn (string $time, int $daysAgo) => $now->modify("-$daysAgo days")->modify($time);

$baristaId = $users->create('Lucía Barista', DEMO_BARISTA_PHONE, password_hash(DEMO_BARISTA_PASSWORD, PASSWORD_DEFAULT), 'barista', $now->modify('-120 days'));
$secondBaristaId = $users->create('Marcos Barista', '600000003', password_hash(DEMO_BARISTA_PASSWORD, PASSWORD_DEFAULT), 'barista', $now->modify('-100 days'));
$staff = [$baristaId, $secondBaristaId];

/**
 * Nombre, teléfono, días de antigüedad y nº de visitas. Cada visita suma 1-2 sellos
 * o, si la tarjeta está llena, canjea la bebida.
 */
$customers = [
    ['Carmen Demo', DEMO_CUSTOMER_PHONE, 60, 17, DEMO_CUSTOMER_PASSWORD],
    ['Álvaro Ruiz', '611000101', 90, 34, null],
    ['Marta Soler', '611000102', 80, 29, null],
    ['Iván Peña', '611000103', 75, 22, null],
    ['Sofía Navarro', '611000104', 70, 20, null],
    ['Nuria Campos', '611000105', 50, 16, null],
    ['Hugo Vidal', '611000106', 45, 12, null],
    ['Elena Ortiz', '611000107', 40, 9, null],
    ['Pablo Marín', '611000108', 30, 7, null],
    ['Lola Herrera', '611000109', 20, 5, null],
    ['Daniel Gil', '611000110', 12, 4, null],
    ['Irene Castro', '611000111', 5, 2, null],
];

foreach ($customers as [$name, $phone, $age, $visits, $password]) {
    $hash = password_hash($password ?? bin2hex(random_bytes(12)), PASSWORD_DEFAULT);
    $id = $users->create($name, $phone, $hash, 'customer', $now->modify("-$age days"));

    // Días de visita aleatorios dentro de su antigüedad, de más antiguo a más reciente.
    $days = [];
    for ($i = 0; $i < $visits; $i++) {
        $days[] = mt_rand(0, $age - 1);
    }
    rsort($days);

    $balance = 0;
    foreach ($days as $daysAgo) {
        $at = $pick(sprintf('%02d:%02d', mt_rand(8, 19), mt_rand(0, 59)) . ':00', $daysAgo);
        $at = min($at, $now); // sin fechas en el futuro
        $staffId = $staff[array_rand($staff)];
        if ($balance >= StampRules::GOAL) {
            $loyalty->redeem($id, $staffId, $at);
            $balance = 0;
        } else {
            $amount = min(mt_rand(1, 2), StampRules::GOAL - $balance);
            $balance = $loyalty->addStamps($id, $staffId, $amount, $at);
        }
    }
}

printf("Datos demo creados. Barista: %s / %s · Cliente: %s / %s\n", DEMO_BARISTA_PHONE, DEMO_BARISTA_PASSWORD, DEMO_CUSTOMER_PHONE, DEMO_CUSTOMER_PASSWORD);
