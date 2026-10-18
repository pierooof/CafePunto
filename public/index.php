<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BaristaController;
use App\Controllers\CustomerController;
use App\Controllers\HealthController;
use App\Core\App;
use App\Core\Request;
use App\Core\Router;

// Servidor embebido de PHP (desarrollo): deja que sirva los ficheros estáticos.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (is_file($file) && $file !== __FILE__) {
        return false;
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';

$router = new Router();
$router->get('/health', [HealthController::class, 'show'], 'open');

$router->get('/', [AuthController::class, 'home']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout'], 'auth');

$router->get('/card', [CustomerController::class, 'card'], 'customer');
$router->get('/c/{token}', [CustomerController::class, 'scan'], 'auth');

$router->get('/barista', [BaristaController::class, 'dashboard'], 'barista');
$router->get('/barista/search', [BaristaController::class, 'search'], 'barista');
$router->get('/barista/customers/{id}', [BaristaController::class, 'show'], 'barista');
$router->post('/barista/customers/{id}/stamps', [BaristaController::class, 'stamp'], 'barista');
$router->post('/barista/customers/{id}/redeem', [BaristaController::class, 'redeem'], 'barista');

(new App($router))->handle(Request::fromGlobals())->send();
