<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use PHPUnit\Framework\TestCase;

use function LaundryBooking\Support\spreadsheet_safe;

final class SecurityTest extends TestCase
{
    public function testSpreadsheetFormulaPrefixesAreNeutralized(): void
    {
        $this->assertSame("'=1+1", spreadsheet_safe('=1+1'));
        $this->assertSame("'  @SUM(A1:A2)", spreadsheet_safe('  @SUM(A1:A2)'));
        $this->assertSame('Anna Hansen', spreadsheet_safe('Anna Hansen'));
    }
}
