<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Services\CodeService;
use PHPUnit\Framework\TestCase;

final class CodeServiceTest extends TestCase
{
    private CodeService $codeService;

    protected function setUp(): void
    {
        $this->codeService = new CodeService();
    }

    public function testGeneratesSixDigitCode(): void
    {
        $code = $this->codeService->generateSixDigitCode();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testGeneratesCodesWithLeadingZeroesEventually(): void
    {
        $sawLeadingZero = false;

        for ($i = 0; $i < 200; $i++) {
            $code = $this->codeService->generateSixDigitCode();
            $this->assertSame(6, strlen($code));

            if ($code[0] === '0') {
                $sawLeadingZero = true;
            }
        }

        $this->assertTrue($sawLeadingZero, 'Expected at least one generated code to start with a leading zero.');
    }

    public function testHashAndVerifyRoundTrip(): void
    {
        $code = '012345';
        $hash = $this->codeService->hashCode($code);

        $this->assertNotSame($code, $hash);
        $this->assertTrue($this->codeService->verifyCode($code, $hash));
        $this->assertFalse($this->codeService->verifyCode('999999', $hash));
    }
}
