<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Http\Request;
use LaundryBooking\Support\Env;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    /** @var array<string,mixed> */
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        Env::reset();
        putenv('TRUST_PROXY_HEADERS=true');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        putenv('TRUST_PROXY_HEADERS');
        Env::reset();
    }

    public function testTrustedRailwayRealIpIsUsed(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.2';
        $_SERVER['HTTP_X_REAL_IP'] = '192.0.2.42';

        $this->assertSame('192.0.2.42', Request::ip());
    }

    public function testForwardedForIsNotTrustedAsClientInput(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.2';
        unset($_SERVER['HTTP_X_REAL_IP']);
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.99';

        $this->assertSame('10.0.0.2', Request::ip());
    }
}
