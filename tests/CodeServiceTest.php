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

    public function testGeneratesConfiguredLengthCancellationCode(): void
    {
        foreach ([4, 6, 8] as $length) {
            for ($index = 0; $index < 20; $index++) {
                $code = $this->codeService->generateCancellationCode($length);
                $this->assertMatchesRegularExpression('/^\d{' . $length . '}$/', $code);
            }
        }
    }

    public function testRejectsUnsupportedCancellationCodeLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->codeService->generateCancellationCode(3);
    }

    public function testHashAndVerifyRoundTrip(): void
    {
        $code = '0427';
        $hash = $this->codeService->hashCode($code);

        $this->assertNotSame($code, $hash);
        $this->assertTrue($this->codeService->verifyCode($code, $hash));
        $this->assertTrue($this->codeService->verifyCode('0427', $hash));
        $this->assertFalse($this->codeService->verifyCode('0428', $hash));
    }

}
