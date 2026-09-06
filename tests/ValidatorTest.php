<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use LaundryBooking\Support\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testAcceptsConfiguredCancellationCodeLength(): void
    {
        $this->assertNull(Validator::validateCancellationCodeFormat('0427', 4));
        $this->assertNull(Validator::validateCancellationCodeFormat('012345', 6));
        $this->assertNull(Validator::validateCancellationCodeFormat('00123456', 8));
    }

    public function testRejectsIncorrectLengthOrNonNumericCode(): void
    {
        $this->assertNotNull(Validator::validateCancellationCodeFormat('123', 4));
        $this->assertNotNull(Validator::validateCancellationCodeFormat('12345', 4));
        $this->assertNotNull(Validator::validateCancellationCodeFormat('12A4', 4));
    }
}
