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
        $this->assertSame('Monday, September 7, 2026', DateHelper::formatLong(new DateTimeImmutable('2026-09-07')));
    }

    public function testDanishPluralization(): void
    {
        I18n::setLocale('da');

        $this->assertSame('1 ledig tid', I18n::plural(1, '%d ledig tid', '%d ledige tider'));
        $this->assertSame('3 ledige tider', I18n::plural(3, '%d ledig tid', '%d ledige tider'));
        $this->assertSame('0 ledige tider', I18n::plural(0, '%d ledig tid', '%d ledige tider'));
    }

    public function testEnglishPluralization(): void
    {
        I18n::setLocale('en');

        $this->assertSame('1 hour', I18n::plural(1, '%d time', '%d timer'));
        $this->assertSame('3 hours', I18n::plural(3, '%d time', '%d timer'));
        $this->assertSame(
            'Too many attempts. Try again in about 1 minute.',
            I18n::plural(1, 'For mange forsøg. Prøv igen om ca. %d minut.', 'For mange forsøg. Prøv igen om ca. %d minutter.')
        );
    }

    public function testActivityLogKeysAreLabelledInDanish(): void
    {
        I18n::setLocale('da');

        $this->assertSame('Booking oprettet', I18n::translate('booking_created'));
        $this->assertSame('Beboer', I18n::translate('resident'));
    }

    public function testUnsupportedLocaleFallsBackToDanish(): void
    {
        I18n::setLocale('de');

        $this->assertSame('da', I18n::locale());
        $this->assertSame('Ledig', I18n::translate('Ledig'));
    }
}
