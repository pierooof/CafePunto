<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use App\Core\Phone;
use App\Core\Router;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CoreTest extends TestCase
{
    /** @return array<string, array{string, ?string}> */
    public static function phones(): array
    {
        return [
            'con espacios' => ['612 345 678', '612345678'],
            'con guiones y paréntesis' => ['(612)-345-678', '612345678'],
            'prefijo internacional' => ['0034 612 345 678', '+34612345678'],
            'con más' => ['+34 612345678', '+34612345678'],
            'demasiado corto' => ['12345', null],
            'letras' => ['612abc678', null],
            'vacío' => ['', null],
        ];
    }

    #[DataProvider('phones')]
    public function testPhoneNormalization(string $raw, ?string $expected): void
    {
        $this->assertSame($expected, Phone::normalize($raw));
    }

    public function testCsrfTokenIsStableAndVerified(): void
    {
        $_SESSION = [];
        $token = Csrf::token();
        $this->assertSame($token, Csrf::token());
        $this->assertTrue(Csrf::verify($token));
        $this->assertFalse(Csrf::verify('otro'));
        $this->assertFalse(Csrf::verify(null));
        $this->assertFalse(Csrf::verify(['array']));
    }

    public function testCsrfRejectsEverythingWithoutSessionToken(): void
    {
        $_SESSION = [];
        $this->assertFalse(Csrf::verify(''));
    }

    public function testRouterMatchesParamsMethodAndRole(): void
    {
        $router = new Router();
        $router->get('/barista/customers/{id}', ['Ctl', 'show'], 'barista');
        $router->post('/barista/customers/{id}/stamps', ['Ctl', 'stamp'], 'barista');

        $match = $router->match('GET', '/barista/customers/42');
        $this->assertSame(['id' => '42'], $match['params']);
        $this->assertSame('barista', $match['role']);

        $this->assertNull($router->match('POST', '/barista/customers/42'));
        $this->assertNull($router->match('GET', '/barista/customers/42/extra'));
        $this->assertNotNull($router->match('POST', '/barista/customers/7/stamps'));
    }
}
