<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Config;
use App\Core\Database;
use App\Core\Response;
use App\Core\Url;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['APP_ENV', 'APP_URL', 'TRUSTED_PROXIES', 'MYSQL_URL', 'MYSQLHOST', 'MYSQLDATABASE', 'MYSQLPORT', 'MYSQLUSER', 'MYSQLPASSWORD'] as $key) {
            Config::set($key, null);
        }
    }

    public function testUrlsAndRedirectsAreBuiltFromAppUrl(): void
    {
        Config::set('APP_URL', 'https://cafe.pierooof.dev/');
        $this->assertSame('https://cafe.pierooof.dev/c/abc', Url::to('/c/abc'));
        $this->assertSame('https://cafe.pierooof.dev/card', Response::redirect('/card')->headers['Location']);
    }

    public function testProductionRequiresHttpsAppUrl(): void
    {
        Config::set('APP_ENV', 'production');
        Config::set('APP_URL', 'http://cafe.pierooof.dev');
        $this->expectException(RuntimeException::class);
        Config::appUrl();
    }

    public function testDevelopmentAllowsHttp(): void
    {
        Config::set('APP_ENV', 'development');
        Config::set('APP_URL', 'http://localhost:8000');
        $this->assertSame('http://localhost:8000', Config::appUrl());
        $this->assertFalse(Config::isProduction());
    }

    public function testTrustedProxiesDefaultsToOne(): void
    {
        $this->assertSame(1, Config::trustedProxies());
        Config::set('TRUSTED_PROXIES', '2');
        $this->assertSame(2, Config::trustedProxies());
    }

    public function testMysqlUrlIsParsedAndDecoded(): void
    {
        $s = Database::settings('mysql://user:p%40ss%2Fword@db.railway.internal:3307/railway');
        $this->assertSame('mysql:host=db.railway.internal;port=3307;dbname=railway;charset=utf8mb4', $s['dsn']);
        $this->assertSame('user', $s['user']);
        $this->assertSame('p@ss/word', $s['password']);
    }

    public function testRailwayStyleVariablesAreUsedWithoutMysqlUrl(): void
    {
        Config::set('MYSQLHOST', 'mysql.railway.internal');
        Config::set('MYSQLPORT', '3306');
        Config::set('MYSQLUSER', 'root');
        Config::set('MYSQLPASSWORD', 'secret');
        Config::set('MYSQLDATABASE', 'railway');

        $s = Database::settings();
        $this->assertSame('mysql:host=mysql.railway.internal;port=3306;dbname=railway;charset=utf8mb4', $s['dsn']);
        $this->assertSame('secret', $s['password']);
    }
}
