<?php

declare(strict_types=1);

// Crea una cuenta de barista.
// Uso: php bin/create-barista.php "Nombre" 600123456 "contraseña"

use App\Core\Database;
use App\Core\Phone;
use App\Models\UserRepository;

require dirname(__DIR__) . '/src/bootstrap.php';

[$script, $name, $rawPhone, $password] = $argv + [null, '', '', ''];
$phone = Phone::normalize((string) $rawPhone);

if ($name === '' || $phone === null || strlen((string) $password) < 8) {
    fwrite(STDERR, "Uso: php bin/create-barista.php \"Nombre\" 600123456 \"contraseña (mín. 8)\"\n");
    exit(1);
}

$users = new UserRepository(Database::connect());
if ($users->findByPhone($phone) !== null) {
    fwrite(STDERR, "Ya existe una cuenta con el teléfono $phone.\n");
    exit(1);
}

$users->create($name, $phone, password_hash($password, PASSWORD_DEFAULT), 'barista');
echo "Barista creado: $name ($phone)\n";
