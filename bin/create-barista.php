<?php

declare(strict_types=1);

// Crea una cuenta de barista.
// Uso: php bin/create-barista.php [--admin] "Nombre" 600123456 "contraseña"
// --admin: además puede gestionar el equipo desde el panel.

use App\Core\Database;
use App\Core\Phone;
use App\Models\UserRepository;

require dirname(__DIR__) . '/src/bootstrap.php';

$args = array_slice($argv, 1);
$isAdmin = in_array('--admin', $args, true);
[$name, $rawPhone, $password] = array_values(array_diff($args, ['--admin'])) + ['', '', ''];
$phone = Phone::normalize((string) $rawPhone);

if ($name === '' || $phone === null || strlen((string) $password) < 8) {
    fwrite(STDERR, "Uso: php bin/create-barista.php [--admin] \"Nombre\" 600123456 \"contraseña (mín. 8)\"\n");
    exit(1);
}

$users = new UserRepository(Database::connect());
if ($users->findByPhone($phone) !== null) {
    fwrite(STDERR, "Ya existe una cuenta con el teléfono $phone.\n");
    exit(1);
}

$users->create($name, $phone, password_hash($password, PASSWORD_DEFAULT), 'barista', null, $isAdmin);
echo "Barista creado: $name ($phone)" . ($isAdmin ? ' [admin]' : '') . "\n";
