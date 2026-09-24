<?php

declare(strict_types=1);

namespace LaundryBooking\Support;

final class I18n
{
    private static string $locale = 'da';

    /** @var array<string,string> */
    private const array EN = [
        // Layout and navigation
        'Vaskekalender' => 'Laundry calendar',
        'Vaskekalender – gå til kalenderen' => 'Laundry calendar – go to calendar',
        'Book en tid i vaskerummet' => 'Book a time in the laundry room',
        'Primær navigation' => 'Primary navigation',
        'Kalender' => 'Calendar',
        'Log ud' => 'Log out',
        'Tilbage' => 'Back',
        'Tilbage til kalenderen' => 'Back to calendar',

        // Access
        'Adgang' => 'Access',
        'Velkommen' => 'Welcome',
        'Indtast ejendommens fælles kode for at se og booke vasketider.' => 'Enter the shared property code to view and book laundry times.',
        'Adgangskode' => 'Access code',
        'Fortsæt' => 'Continue',
        'Forkert kode. Prøv igen.' => 'Incorrect code. Please try again.',
        'Sikkerhedstjekket mislykkedes. Prøv igen.' => 'The security check failed. Please try again.',
        'For mange forsøg. Prøv igen om ca. %d minut.' => 'Too many attempts. Try again in about %d minute.',
        'For mange forsøg. Prøv igen om ca. %d minutter.' => 'Too many attempts. Try again in about %d minutes.',

        // Booking
        'Bekræft booking' => 'Confirm booking',
        'Bekræft din tid' => 'Confirm your time',
        'Kl. %s' => '%s',
        'kl. %s' => '%s',
        'Tiden er allerede i gang – du har maskinerne til kl. %s.' => 'This time has already started – the machines are yours until %s.',
        'Dit navn' => 'Your name',
        'Navnet vises i kalenderen, så andre kan se, hvem der har tiden. Fornavn og lejlighed er nok.' => 'Your name is shown in the calendar so others can see who has the time. First name and flat number are enough.',
        'Godt at vide' => 'Good to know',
        'Er du ikke i gang senest %d minutter efter starttid, må en anden beboer overtage tiden.' => 'If you have not started within %d minutes of the start time, another resident may take over.',
        'Er du ikke i gang senest %d minutter efter, at du har booket, må en anden beboer overtage tiden.' => 'If you have not started within %d minutes of booking, another resident may take over.',
        'Når du har booket, får du en aflysningskode. Gem den, så du kan aflyse senere.' => 'After booking, you get a cancellation code. Keep it so you can cancel later.',
        'Tiden er desværre allerede booket.' => 'Sorry, this time is already booked.',
        'Booking oprettet' => 'Booking created',
        'Din tid er booket' => 'Your time is booked',
        'Din aflysningskode' => 'Your cancellation code',
        'Tag et billede af koden eller skriv den ned – den bliver ikke vist igen.' => 'Take a photo of the code or write it down – it will not be shown again.',
        'Går tilbage til kalenderen om %d sek.' => 'Returning to the calendar in %d s.',

        // Cancellation
        'Aflys' => 'Cancel',
        'Aflys booking' => 'Cancel booking',
        'Aflysningskode' => 'Cancellation code',
        '%d cifre' => '%d digits',
        'Indtast den aflysningskode, du fik, da du bookede tiden.' => 'Enter the cancellation code you received when you booked.',
        'Din booking er aflyst.' => 'Your booking has been cancelled.',
        'Bookingen findes ikke længere.' => 'The booking no longer exists.',

        // Calendar
        'I dag' => 'Today',
        'I morgen' => 'Tomorrow',
        'Om %d dag' => 'In %d day',
        'Om %d dage' => 'In %d days',
        'Forrige dag' => 'Previous day',
        'Næste dag' => 'Next day',
        'Vælg dato' => 'Choose date',
        'Vælg dag' => 'Choose a day',
        'Vis' => 'Show',
        'Forrige måned' => 'Previous month',
        'Næste måned' => 'Next month',
        'Luk kalenderen' => 'Close calendar',
        'Man' => 'Mon',
        'Tir' => 'Tue',
        'Ons' => 'Wed',
        'Tor' => 'Thu',
        'Fre' => 'Fri',
        'Lør' => 'Sat',
        'Søn' => 'Sun',
        'Ledig tid' => 'Available time',
        'Booket eller overstået' => 'Booked or over',
        '%d ledig tid' => '%d available time',
        '%d ledige tider' => '%d available times',
        'Fuldt booket' => 'Fully booked',
        'Vasketider for %s' => 'Laundry times for %s',
        'Book tiden fra %s til %s' => 'Book the time from %s to %s',
        'Aflys bookingen for %s fra %s til %s' => 'Cancel the booking for %s from %s to %s',
        'Ledig' => 'Available',
        'Ledig nu' => 'Available now',
        'Tiden er i gang' => 'Already started',
        'Kan bookes til kl. %s' => 'Can be booked until %s',
        'Vaskemaskine og tørretumbler' => 'Washing machine & dryer',
        'Book tid' => 'Book time',
        'Booket' => 'Booked',
        'I gang nu' => 'In use now',
        'Tid brugt' => 'Time used',
        'Kan overtages kl. %s, hvis tiden ikke er taget i brug' => 'Can be taken over at %s if the time is not in use',
        'Står maskinerne stille? Så må tiden overtages.' => 'Machines not running? Then the time may be taken over.',
        'Lukket for booking' => 'Closed for booking',
        'Tiden er i gang og kan ikke længere bookes' => 'Already started and can no longer be booked',
        'Overstået' => 'Over',
        'Kan ikke bookes' => 'Cannot be booked',
        '%d time' => '%d hour',
        '%d timer' => '%d hours',

        // Weather and tips
        'Godt tørrevejr' => 'Good drying weather',
        'Tørrevejr' => 'Drying weather',
        'i dag' => 'today',
        'i morgen' => 'tomorrow',
        '%d °C' => '%d °C',
        '%d %% risiko for regn' => '%d%% chance of rain',
        '%d m/s' => '%d m/s',
        'Vejrdata fra Open-Meteo' => 'Weather data from Open-Meteo',
        'Dagens vasketip' => 'Laundry tip of the day',
        'Det ser lovende ud til udendørs tørring – måske kan tørretumbleren få en fridag.' => 'Conditions look promising for outdoor drying – perhaps the tumble dryer can have a day off.',
        'Der er meldt regn. Tørretumbleren eller et tørrestativ indendørs er det sikre valg.' => 'Rain is forecast. The tumble dryer or an indoor drying rack is the safe choice.',
        'Der kan komme byger. Hænger du tøjet ud, så hold øje med himlen.' => 'Showers are possible. If you hang your laundry outside, keep an eye on the sky.',
        'Regnvejr' => 'Rainy weather',
        'Det bliver blæsende. Tøjet tørrer hurtigt, men husk ekstra tøjklemmer.' => 'It will be windy. Laundry dries quickly, but bring extra pegs.',
        'Det bliver køligt, så tøjet tørrer nok langsommere udenfor.' => 'It will be cool, so laundry will probably dry more slowly outside.',
        'Tørreforholdene er blandede. Tjek vejret igen, før du hænger tøjet ud.' => 'Drying conditions are mixed. Check the weather again before hanging laundry outside.',
        'Ryst tøjet godt, inden du hænger det op. Det giver færre folder og kortere tørretid.' => 'Shake laundry well before hanging it. This reduces creases and drying time.',
        'Lad lågen til vaskemaskinen stå på klem efter brug, så maskinen kan tørre og holde sig frisk.' => 'Leave the washing-machine door slightly open after use so it can dry and stay fresh.',
        'Et ekstra centrifugeringsprogram kan forkorte tørretiden for håndklæder og sengetøj.' => 'An extra spin cycle can shorten drying time for towels and bed linen.',
        'Fyld ikke tromlen helt – der skal være cirka en håndsbredde fri plads i toppen.' => 'Do not overfill the drum – leave about a hand width of free space at the top.',
        'Vend mørkt tøj på vrangen før vask. Så holder farven sig pæn længere.' => 'Turn dark clothes inside out before washing to help their colour last longer.',
        'Sortér efter både farve og materiale. Tunge håndklæder og let tøj tørrer bedst hver for sig.' => 'Sort by both colour and material. Heavy towels and light garments dry best separately.',
        'Tør gerne tøjet udenfor, når vejret tillader det – frisk luft er både gratis og skånsom.' => 'Dry laundry outside when weather permits – fresh air is free and gentle on clothes.',

        // Validation
        'Skriv dit navn.' => 'Enter your name.',
        'Navnet er for kort.' => 'The name is too short.',
        'Navnet må højst være på 100 tegn.' => 'The name may contain at most 100 characters.',
        'Skriv aflysningskoden.' => 'Enter the cancellation code.',
        'Aflysningskoden skal bestå af %d cifre.' => 'The cancellation code must contain %d digits.',
        'Tidspunktet er ugyldigt.' => 'The time is invalid.',
        'Datoen er ikke gyldig.' => 'The date is invalid.',
        'Du kan ikke booke en tid i fortiden.' => 'You cannot book a time in the past.',
        'Du kan højst booke %d uger frem.' => 'You can book at most %d weeks ahead.',
        'Tiden kan ikke længere bookes.' => 'This time can no longer be booked.',
        'Aflysningskoden er ikke korrekt.' => 'The cancellation code is incorrect.',
        'Der opstod en uventet fejl. Prøv igen senere.' => 'An unexpected error occurred. Please try again later.',

        // Admin
        'Administrator login' => 'Administrator login',
        'Brugernavn' => 'Username',
        'Log ind' => 'Log in',
        'Forkert brugernavn eller adgangskode.' => 'Incorrect username or password.',
        'Administration' => 'Administration',
        'Bookinger' => 'Bookings',
        'Indstillinger' => 'Settings',
        'Eksport (CSV)' => 'Export (CSV)',
        'Bookinger i alt' => 'Total bookings',
        'Bookinger i dag' => 'Bookings today',
        'Bookinger de næste 7 dage' => 'Bookings in the next 7 days',
        'Seneste oprydning' => 'Latest cleanup',
        'Ukendt' => 'Unknown',
        'PHP-version' => 'PHP version',
        'Database' => 'Database',
        'Tilbage til administration' => 'Back to administration',
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
        'Gem' => 'Save',
        'Hvor mange uger frem kan der bookes?' => 'How many weeks ahead can residents book?',
        'Hvor længe efter starttid kan en ledig tid bookes? (minutter)' => 'How long after the start can a free time still be booked? (minutes)',
        '0 betyder, at en tid kun kan bookes før starttid. Med 120 kan tiden 07.00–10.00 bookes indtil kl. 09.00.' => '0 means a time can only be booked before it starts. With 120, the 07:00–10:00 time can be booked until 09:00.',
        'Besked i kalenderen (valgfri)' => 'Calendar message (optional)',
        'Postnummer til tørrevejr' => 'Postcode for drying forecast',
        'Bruges til vejrudsigten i kalenderen. Standard er 1352 København K.' => 'Used for the calendar forecast. The default is 1352 Copenhagen K.',
        'Antal cifre i aflysningskoden' => 'Number of digits in cancellation code',
        'Mellem 4 og 8 cifre. En ændring gælder straks, så brug helst den samme længde under aktive bookinger.' => 'Between 4 and 8 digits. Changes apply immediately, so keep the same length while bookings are active.',
        'Ny ejendomskode (lad feltet stå tomt for at beholde den nuværende)' => 'New property code (leave blank to keep the current one)',
        '4-8 cifre. Beboerne taster koden på iPad’ens taltastatur.' => '4–8 digits. Residents enter the code on the iPad number pad.',
        'Antal uger skal være mellem 1 og 52.' => 'The number of weeks must be between 1 and 52.',
        'Minutter efter starttid skal være mellem 0 og %d.' => 'Minutes after the start must be between 0 and %d.',
        'Postnummeret skal bestå af 4 cifre.' => 'The postcode must contain 4 digits.',
        'Aflysningskoden skal være mellem 4 og 8 cifre.' => 'The cancellation code must be between 4 and 8 digits.',
        'Den nye ejendomskode skal bestå af 4-8 cifre.' => 'The new property code must contain 4–8 digits.',
        'Indstillingerne er gemt.' => 'The settings have been saved.',
        'Aktivitetslog' => 'Activity log',
        'Tidspunkt' => 'Time',
        'Aktør' => 'Actor',
        'Booking-id' => 'Booking ID',
        'IP' => 'IP',
        'Detaljer' => 'Details',
        'Ingen aktivitet endnu.' => 'No activity yet.',
        'ok' => 'OK',
        'error' => 'Error',
        'resident' => 'Resident',
        'admin' => 'Administrator',
        'system' => 'System',
        'booking_created' => 'Booking created',
        'booking_cancel_failed' => 'Failed cancellation',
        'booking_cancelled' => 'Booking cancelled',
        'booking_deleted' => 'Booking deleted',
        'settings_updated' => 'Settings updated',
        'property_code_changed' => 'Property code changed',
        'bookings_exported' => 'Bookings exported',
        'cleanup_old_bookings' => 'Old bookings cleaned up',
    ];

    /** @var array<string,string> Danish labels for activity-log keys stored in the database. */
    private const array DA = [
        'ok' => 'OK',
        'error' => 'Fejl',
        'resident' => 'Beboer',
        'admin' => 'Administrator',
        'system' => 'System',
        'booking_created' => 'Booking oprettet',
        'booking_cancel_failed' => 'Mislykket aflysning',
        'booking_cancelled' => 'Booking aflyst',
        'booking_deleted' => 'Booking slettet',
        'settings_updated' => 'Indstillinger opdateret',
        'property_code_changed' => 'Ejendomskode ændret',
        'bookings_exported' => 'Bookinger eksporteret',
        'cleanup_old_bookings' => 'Gamle bookinger ryddet op',
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
        $text = self::$locale === 'en' ? (self::EN[$source] ?? $source) : (self::DA[$source] ?? $source);

        return $values === [] ? $text : vsprintf($text, $values);
    }

    /**
     * Picks the singular or plural Danish source by $count, translates it and inserts $count.
     */
    public static function plural(int $count, string $singular, string $plural): string
    {
        return self::translate(abs($count) === 1 ? $singular : $plural, $count);
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

function tn(int $count, string $singular, string $plural): string
{
    return I18n::plural($count, $singular, $plural);
}

function current_locale(): string
{
    return I18n::locale();
}
