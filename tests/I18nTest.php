<?php

declare(strict_types=1);

namespace LaundryBooking\Tests;

use DateTimeImmutable;
use LaundryBooking\Support\DateHelper;
use LaundryBooking\Support\I18n;
use PHPUnit\Framework\TestCase;

final class I18nTest extends TestCase
{
    protected function tearDown(): void
    {
        I18n::setLocale('da');
    }

    public function testDanishIsTheDefaultLocale(): void
    {
        I18n::setLocale('da');

        $this->assertSame('Book tid', I18n::translate('Book tid'));
        $this->assertSame('mandag 7. september 2026', DateHelper::formatLong(new DateTimeImmutable('2026-09-07')));
    }

    public function testEnglishTranslationsAndFormatting(): void
    {
        I18n::setLocale('en');

        $this->assertSame('Book time', I18n::translate('Book tid'));
        $this->assertSame('4 available', I18n::translate('%d ledige', 4));
        $this->assertSame('Monday, September 7, 2026', DateHelper::formatLong(new DateTimeImmutable('2026-09-07')));
    }

    public function testUnsupportedLocaleFallsBackToDanish(): void
    {
        I18n::setLocale('de');

        $this->assertSame('da', I18n::locale());
        $this->assertSame('Ledig', I18n::translate('Ledig'));
    }
}
