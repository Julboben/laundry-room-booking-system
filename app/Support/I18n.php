<?php

declare(strict_types=1);

namespace LaundryBooking\Support;

final class I18n
{
    private static string $locale = 'da';

    /** @var array<string,string> */
    private const array EN = [
        'Vaskekalender' => 'Laundry calendar',
        'Vaskekalender – gå til kalenderen' => 'Laundry calendar – go to calendar',
        'Book en tid i vaskerummet' => 'Book a time in the laundry room',
        'Primær navigation' => 'Primary navigation',
        'Kalender' => 'Calendar',
        'Log ud' => 'Log out',
        'Adgang' => 'Access',
        'Ejendomskode' => 'Property code',
        'Indtast ejendommens fælles adgangskode for at se og booke tider i vaskekalenderen.' => 'Enter the shared property code to view and book laundry times.',
        'Gem' => 'Save',
        'Sikkerhedstjek fejlede. Prøv igen.' => 'The security check failed. Please try again.',
        'For mange forsøg. Prøv igen om ca. %d minutter.' => 'Too many attempts. Try again in about %d minutes.',
        'Koden er ikke korrekt. Prøv igen.' => 'The code is incorrect. Please try again.',
        'Bekræft booking' => 'Confirm booking',
        'Bekræft din tid' => 'Confirm your time',
        'Dit navn' => 'Your name',
        'Navnet vises på den bookede tid.' => 'The name is shown on the booked time.',
        'Godt at vide' => 'Good to know',
        'Er tiden ikke taget i brug senest %d minutter efter start, må en anden beboer overtage den.' => 'If the time is not in use within %d minutes after it starts, another resident may take it over.',
        'Efter bookingen får du en aflysningskode. Gem den, hvis du får brug for at aflyse.' => 'After booking, you receive a cancellation code. Save it in case you need to cancel.',
        'Tilbage' => 'Back',
        'Tiden er desværre allerede booket.' => 'Unfortunately, this time is already booked.',
        'Booking oprettet' => 'Booking created',
        'Din booking er oprettet.' => 'Your booking has been created.',
        'Navn:' => 'Name:',
        'Din aflysningskode:' => 'Your cancellation code:',
        'Gem denne aflysningskode. Du skal bruge den, hvis du vil aflyse bookingen.' => 'Save this cancellation code. You will need it to cancel the booking.',
        'Tilbage til kalenderen' => 'Back to calendar',
        'Aflys booking' => 'Cancel booking',
        'Bookingen er blevet fjernet.' => 'The booking has been removed.',
        'Bookingen findes ikke.' => 'The booking could not be found.',
        'Bookingen bliver fjernet, hvis koden er korrekt.' => 'The booking will be removed if the code is correct.',
        'Aflysningskode' => 'Cancellation code',
        '%d cifre' => '%d digits',
        'I dag' => 'Today',
        'Forrige' => 'Previous',
        'Forrige dag' => 'Previous day',
        'Næste' => 'Next',
        'Næste dag' => 'Next day',
        'Vælg en anden dag' => 'Choose another day',
        'Vis' => 'Show',
        'Vælg dag' => 'Choose date',
        'Forrige måned' => 'Previous month',
        'Næste måned' => 'Next month',
        'Luk kalenderen' => 'Close calendar',
        '%d ledige' => '%d available',
        '%s, %d ledige tider' => '%s, %d available times',
        'Vasketider for %s' => 'Laundry times for %s',
        'Aflys bookingen for %s fra %s til %s' => 'Cancel the booking for %s from %s to %s',
        'Book tiden fra %s til %s' => 'Book the time from %s to %s',
        'Passeret' => 'Passed',
        'Kan ikke bookes' => 'Unavailable',
        'Optaget' => 'Occupied',
        'Ledig' => 'Available',
        'Book tid' => 'Book time',
        '1 time' => '1 hour',
        '%d timer' => '%d hours',
        'Godt tørrevejr' => 'Good drying weather',
        'Tørrevejret' => 'Drying weather',
        '%d%% regn' => '%d%% rain',
        '%d km/t vind' => '%d km/h wind',
        'Vejrdata fra Open-Meteo' => 'Weather data from Open-Meteo',
        'Dagens vasketip' => 'Laundry tip of the day',
        'i dag' => 'today',
        'i morgen' => 'tomorrow',
        'Det ser lovende ud til udendørs tørring – måske kan tørretumbleren få en fridag.' => 'Conditions look promising for outdoor drying – perhaps the tumble dryer can have a day off.',
        'Der er risiko for regn, så hold øje med tøjet eller tør det indenfor.' => 'There is a risk of rain, so keep an eye on the laundry or dry it indoors.',
        'Det bliver blæsende. Tøjet kan tørre hurtigt, men sørg for at fastgøre det godt.' => 'It will be windy. Laundry may dry quickly, but make sure it is secured well.',
        'Det bliver køligt, så tøjet vil sandsynligvis tørre langsommere udenfor.' => 'It will be cool, so laundry will probably dry more slowly outside.',
        'Tørreforholdene er blandede. Tjek vejret igen, før du hænger tøjet ud.' => 'Drying conditions are mixed. Check the weather again before hanging laundry outside.',
        'Ryst tøjet godt, inden du hænger det op. Det giver færre folder og kortere tørretid.' => 'Shake laundry well before hanging it. This reduces creases and drying time.',
        'Lad lågen til vaskemaskinen stå på klem efter brug, så maskinen kan tørre og holde sig frisk.' => 'Leave the washing-machine door slightly open after use so it can dry and stay fresh.',
        'Et ekstra centrifugeringsprogram kan forkorte tørretiden for håndklæder og sengetøj.' => 'An extra spin cycle can shorten drying time for towels and bed linen.',
        'Fyld tromlen uden at presse tøjet sammen – cirka en håndsbredde fri plads er en god tommelfingerregel.' => 'Fill the drum without compressing the laundry – about a hand width of free space is a good rule.',
        'Vend mørkt tøj på vrangen før vask. Det hjælper farven med at holde sig pæn længere.' => 'Turn dark clothes inside out before washing to help their colour last longer.',
        'Sortér efter både farve og materiale. Tunge håndklæder og let tøj tørrer bedst hver for sig.' => 'Sort by both colour and material. Heavy towels and light garments dry best separately.',
        'Tør gerne tøjet udenfor, når vejret tillader det – frisk luft er både gratis og skånsom.' => 'Dry laundry outside when weather permits – fresh air is free and gentle on clothes.',
        'Skriv et navn.' => 'Enter a name.',
        'Navnet er for kort.' => 'The name is too short.',
        'Navnet må højst være 100 tegn.' => 'The name may contain at most 100 characters.',
        'Skriv aflysningskoden.' => 'Enter the cancellation code.',
        'Aflysningskoden skal bestå af %d cifre.' => 'The cancellation code must contain %d digits.',
        'Tiden er ikke gyldig.' => 'The time is invalid.',
        'Datoen er ikke gyldig.' => 'The date is invalid.',
        'Du kan ikke booke en tid i fortiden.' => 'You cannot book a time in the past.',
        'Datoen ligger for langt ude i fremtiden.' => 'The date is too far in the future.',
        'Denne tid er allerede startet.' => 'This time has already started.',
        'Aflysningskoden er ikke korrekt.' => 'The cancellation code is incorrect.',
        'Der opstod en uventet fejl. Prøv igen senere.' => 'An unexpected error occurred. Please try again later.',
        'Administrator login' => 'Administrator login',
        'Brugernavn' => 'Username',
        'Adgangskode' => 'Password',
        'Log ind' => 'Log in',
        'Forkert brugernavn eller adgangskode.' => 'Incorrect username or password.',
        'Administration' => 'Administration',
        'Bookinger' => 'Bookings',
        'Indstillinger' => 'Settings',
        'Logs' => 'Logs',
        'Eksport (CSV)' => 'Export (CSV)',
        'Bookinger i alt' => 'Total bookings',
        'Bookinger i dag' => 'Bookings today',
        'Bookinger de næste 7 dage' => 'Bookings in the next 7 days',
        'Seneste oprydning' => 'Latest cleanup',
        'Ukendt' => 'Unknown',
        'PHP-version' => 'PHP version',
        'Database' => 'Database',
        '« Tilbage til administration' => '« Back to administration',
        'Fra dato' => 'From date',
        'Til dato' => 'To date',
        'Søg navn' => 'Search name',
        'Filtrer' => 'Filter',
        'Dato' => 'Date',
        'Tid' => 'Time',
        'Navn' => 'Name',
        'Oprettet' => 'Created',
        'Handling' => 'Action',
        'Slet denne booking?' => 'Delete this booking?',
        'Slet' => 'Delete',
        'Ingen bookinger fundet.' => 'No bookings found.',
        'Bookingen er slettet.' => 'The booking was deleted.',
        'Antal uger frem, der kan bookes' => 'Number of weeks available for booking',
        'Besked i kalenderen (valgfri)' => 'Calendar message (optional)',
        'Postnummer til tørrevejr' => 'Postcode for drying forecast',
        'Bruges til vejrudsigten i kalenderen. Standard er 1352 København K.' => 'Used for the calendar forecast. The default is 1352 Copenhagen K.',
        'Antal cifre i aflysningskoden' => 'Number of digits in cancellation code',
        'Mellem 4 og 8 cifre. En ændring gælder straks, så brug helst den samme længde under aktive bookinger.' => 'Between 4 and 8 digits. Changes apply immediately, so keep the same length while bookings are active.',
        'Ny ejendomskode (lad stå tom for ikke at ændre)' => 'New property code (leave blank to keep unchanged)',
        'Antal uger skal være mellem 1 og 52.' => 'The number of weeks must be between 1 and 52.',
        'Postnummeret skal bestå af 4 cifre.' => 'The postcode must contain 4 digits.',
        'Aflysningskoden skal være mellem 4 og 8 cifre.' => 'The cancellation code must be between 4 and 8 digits.',
        'Den nye ejendomskode skal være mindst 4 tegn.' => 'The new property code must contain at least 4 characters.',
        'Indstillingerne er gemt.' => 'The settings have been saved.',
        'Aktivitetslog' => 'Activity log',
        'Tidspunkt' => 'Time',
        'Aktør' => 'Actor',
        'Booking-id' => 'Booking ID',
        'IP' => 'IP',
        'Detaljer' => 'Details',
        'Ingen aktivitet endnu.' => 'No activity yet.',
    ];

    private const array DAYS = [
        'da' => [1 => 'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag', 'søndag'],
        'en' => [1 => 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
    ];

    private const array MONTHS = [
        'da' => [1 => 'januar', 'februar', 'marts', 'april', 'maj', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'december'],
        'en' => [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    ];

    public static function setLocale(string $locale): void
    {
        self::$locale = in_array($locale, ['da', 'en'], true) ? $locale : 'da';
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function translate(string $source, int|string|float ...$values): string
    {
        $text = self::$locale === 'en' ? (self::EN[$source] ?? $source) : $source;

        return $values === [] ? $text : vsprintf($text, $values);
    }

    public static function dayName(int $isoDay): string
    {
        return self::DAYS[self::$locale][$isoDay];
    }

    public static function monthName(int $month): string
    {
        return self::MONTHS[self::$locale][$month];
    }
}

function t(string $source, int|string|float ...$values): string
{
    return I18n::translate($source, ...$values);
}

function current_locale(): string
{
    return I18n::locale();
}
