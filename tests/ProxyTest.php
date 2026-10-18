<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Proxy;
use PHPUnit\Framework\TestCase;

final class ProxyTest extends TestCase
{
    public function testHttpsIsDetectedFromForwardedProto(): void
    {
        $this->assertTrue(Proxy::isHttps(['HTTP_X_FORWARDED_PROTO' => 'https'], 1));
        $this->assertTrue(Proxy::isHttps(['HTTP_X_FORWARDED_PROTO' => 'https,http'], 2));
        $this->assertFalse(Proxy::isHttps(['HTTP_X_FORWARDED_PROTO' => 'http'], 1));
        $this->assertFalse(Proxy::isHttps([], 1));
    }

    public function testForwardedProtoIsIgnoredWithoutTrustedProxies(): void
    {
        $this->assertFalse(Proxy::isHttps(['HTTP_X_FORWARDED_PROTO' => 'https'], 0));
        $this->assertTrue(Proxy::isHttps(['HTTPS' => 'on'], 0));
        $this->assertFalse(Proxy::isHttps(['HTTPS' => 'off'], 0));
    }

    public function testClientIpWithOneProxy(): void
    {
        $server = ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.7'];
        $this->assertSame('203.0.113.7', Proxy::clientIp($server, 1));
    }

    public function testSpoofedForwardedForIsIgnored(): void
    {
        // El cliente mandó "1.2.3.4"; el proxy añadió la IP real a la derecha.
        $server = ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 203.0.113.7'];
        $this->assertSame('203.0.113.7', Proxy::clientIp($server, 1));
    }

    public function testClientIpWithTwoProxies(): void
    {
        $server = ['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_FOR' => '203.0.113.7, 10.0.0.1'];
        $this->assertSame('203.0.113.7', Proxy::clientIp($server, 2));
    }

    public function testCloudflareHeaderWinsWhenProxiesAreTrusted(): void
    {
        $server = ['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_FOR' => '9.9.9.9', 'HTTP_CF_CONNECTING_IP' => '203.0.113.7'];
        $this->assertSame('203.0.113.7', Proxy::clientIp($server, 1));
        // Sin proxies de confianza cualquiera podría falsificarla.
        $this->assertSame('10.0.0.2', Proxy::clientIp($server, 0));
    }

    public function testInvalidForwardedValuesFallBackToRemoteAddr(): void
    {
        $server = ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => 'no-es-una-ip', 'HTTP_CF_CONNECTING_IP' => 'basura'];
        $this->assertSame('10.0.0.1', Proxy::clientIp($server, 1));
    }
}
