<?php

declare(strict_types=1);

use App\Core\Config;

// Punto de entrada común (web y scripts de bin/): autoload, .env local y zona horaria.
require dirname(__DIR__) . '/vendor/autoload.php';

Config::load(dirname(__DIR__) . '/.env');
date_default_timezone_set(Config::timezone());
