<?php

declare(strict_types=1);

namespace Tests;

use App\Services\LoginThrottle;
use DateTimeImmutable;

final class LoginThrottleTest extends DatabaseTestCase
{
    private LoginThrottle $throttle;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->throttle = new LoginThrottle($this->pdo);
        $this->now = new DateTimeImmutable('2026-10-18 12:00:00');
    }

    public function testPhoneIsLockedAfterTooManyFailures(): void
    {
        for ($i = 0; $i < LoginThrottle::MAX_PER_PHONE - 1; $i++) {
            $this->throttle->recordFailure('1.1.1.1', '600000001', $this->now);
        }
        $this->assertSame(0, $this->throttle->retryAfter('1.1.1.1', '600000001', $this->now));

        $this->throttle->recordFailure('1.1.1.1', '600000001', $this->now);
        $this->assertSame(LoginThrottle::WINDOW_SECONDS, $this->throttle->retryAfter('1.1.1.1', '600000001', $this->now));
        // Otro teléfono desde otra IP no se ve afectado.
        $this->assertSame(0, $this->throttle->retryAfter('2.2.2.2', '600000002', $this->now));
    }

    public function testLockExpiresWithTheWindow(): void
    {
        for ($i = 0; $i < LoginThrottle::MAX_PER_PHONE; $i++) {
            $this->throttle->recordFailure('1.1.1.1', '600000001', $this->now);
        }
        $later = $this->now->modify('+10 minutes');
        $this->assertSame(300, $this->throttle->retryAfter('1.1.1.1', '600000001', $later));
        $this->assertSame(0, $this->throttle->retryAfter('1.1.1.1', '600000001', $this->now->modify('+15 minutes +1 second')));
    }

    public function testIpIsLockedWhenSweepingManyPhones(): void
    {
        for ($i = 0; $i < LoginThrottle::MAX_PER_IP; $i++) {
            $this->throttle->recordFailure('9.9.9.9', '6000000' . $i, $this->now);
        }
        $this->assertGreaterThan(0, $this->throttle->retryAfter('9.9.9.9', '600999999', $this->now));
        $this->assertSame(0, $this->throttle->retryAfter('8.8.8.8', '600999999', $this->now));
    }

    public function testSuccessfulLoginClearsFailures(): void
    {
        for ($i = 0; $i < LoginThrottle::MAX_PER_PHONE; $i++) {
            $this->throttle->recordFailure('1.1.1.1', '600000001', $this->now);
        }
        $this->throttle->clear('600000001');
        $this->assertSame(0, $this->throttle->retryAfter('1.1.1.1', '600000001', $this->now));
    }
}
