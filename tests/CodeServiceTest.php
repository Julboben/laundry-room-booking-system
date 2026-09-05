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

    public function testGeneratesHighEntropyCancellationCode(): void
    {
        $code = $this->codeService->generateCancellationCode();

        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{4}(?:-[2-9A-HJ-NP-Z]{4}){3}$/', $code);
    }

    public function testGeneratedCodesAreNotRepeatedInSample(): void
    {
        $codes = [];

        for ($i = 0; $i < 200; $i++) {
            $codes[] = $this->codeService->generateCancellationCode();
        }

        $this->assertCount(200, array_unique($codes));
    }

    public function testHashAndVerifyRoundTrip(): void
    {
        $code = '2345-6789-ABCD-EFGH';
        $hash = $this->codeService->hashCode($code);

        $this->assertNotSame($code, $hash);
        $this->assertTrue($this->codeService->verifyCode($code, $hash));
        $this->assertTrue($this->codeService->verifyCode('23456789abcdefgh', $hash));
        $this->assertFalse($this->codeService->verifyCode('9999-9999-9999-9999', $hash));
    }

    public function testLegacySixDigitCodesStillVerify(): void
    {
        $hash = password_hash('012345', PASSWORD_DEFAULT);

        $this->assertTrue($this->codeService->verifyCode('012345', $hash));
    }
}
