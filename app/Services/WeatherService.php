<?php

declare(strict_types=1);

namespace LaundryBooking\Services;

use DateTimeImmutable;
use LaundryBooking\Models\Setting;
use LaundryBooking\Support\DateHelper;
use LaundryBooking\Support\I18n;

/**
 * Fetches and caches a simple outdoor drying forecast.
 *
 * The raw forecast is cached in the settings table so the scheduled
 * refresh job and the web service share it. Page requests only fetch
 * inline when the cache is missing or old, so a working cron job keeps
 * the kiosk from ever waiting on the weather APIs.
 * Weather failures are deliberately non-fatal to the booking flow.
 */
final class WeatherService
{
    private const int INLINE_REFRESH_AFTER_SECONDS = 7200;
    private const int STALE_CACHE_TTL_SECONDS = 21600;
    private const int EVENING_HOUR = 18;
    private const int MAX_GOOD_WIND_SPEED = 10;

    public function __construct(
        private readonly Setting $settings
    ) {
    }

    /**
     * @return array{
     *   period:string,
     *   location:string,
     *   temperature:int,
     *   rainProbability:int,
     *   windSpeed:int,
     *   isGoodDryingWeather:bool,
     *   variant:string,
     *   recommendation:string
     * }|null
     */
    public function getDryingForecast(string $postcode, ?DateTimeImmutable $now = null): ?array
    {
        if (!$this->isValidPostcode($postcode)) {
            return null;
        }

        $now ??= DateHelper::now();
        $cached = $this->readCache($postcode);

        if ($cached === null || $cached['cachedAt'] < time() - self::INLINE_REFRESH_AFTER_SECONDS) {
            $fresh = $this->fetchForecast($postcode);

            if ($fresh !== null) {
                $this->writeCache($postcode, $fresh);
                $cached = $fresh;
            }
        }

        if ($cached === null || $cached['cachedAt'] < time() - self::STALE_CACHE_TTL_SECONDS) {
            return null;
        }

        return $this->forecastFor($cached, $now);
    }

    /**
     * Fetches a new forecast and stores it. Used by the scheduled job.
     */
    public function refresh(string $postcode): bool
    {
        if (!$this->isValidPostcode($postcode)) {
            return false;
        }

        $forecast = $this->fetchForecast($postcode);

        if ($forecast === null) {
            return false;
        }

        $this->writeCache($postcode, $forecast);

        return true;
    }

    private function isValidPostcode(string $postcode): bool
    {
        return preg_match('/^\d{4}$/', $postcode) === 1;
    }

    private function cacheKey(string $postcode): string
    {
        return 'weather_cache_' . $postcode;
    }

    /**
     * @return array{cachedAt:int,location:string,days:list<array<string,mixed>>}|null
     */
    private function readCache(string $postcode): ?array
    {
        $contents = $this->settings->get($this->cacheKey($postcode));

        if ($contents === null || $contents === '') {
            return null;
        }

        $decoded = json_decode($contents, true);

        if (
            !is_array($decoded)
            || !is_int($decoded['cachedAt'] ?? null)
            || !is_string($decoded['location'] ?? null)
            || !is_array($decoded['days'] ?? null)
        ) {
            return null;
        }

        return $decoded;
    }

    /** @param array{cachedAt:int,location:string,days:list<array<string,mixed>>} $forecast */
    private function writeCache(string $postcode, array $forecast): void
    {
        $payload = json_encode($forecast, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload !== false) {
            $this->settings->set($this->cacheKey($postcode), $payload);
        }
    }

    /**
     * @return array{cachedAt:int,location:string,days:list<array<string,mixed>>}|null
     */
    private function fetchForecast(string $postcode): ?array
    {
        $location = $this->requestJson('https://api.dataforsyningen.dk/postnumre/' . rawurlencode($postcode));
        $center = $location['visueltcenter'] ?? null;

        if (!is_array($center) || count($center) < 2 || !is_numeric($center[0]) || !is_numeric($center[1])) {
            return null;
        }

        $query = http_build_query([
            'latitude' => (float) $center[1],
            'longitude' => (float) $center[0],
            'daily' => 'temperature_2m_max,precipitation_sum,precipitation_probability_max,wind_speed_10m_max',
            'wind_speed_unit' => 'ms',
            'timezone' => 'Europe/Copenhagen',
            'forecast_days' => 3,
        ]);
        $weather = $this->requestJson('https://api.open-meteo.com/v1/forecast?' . $query);
        $daily = $weather['daily'] ?? null;

        if (!is_array($daily) || !is_array($daily['time'] ?? null)) {
            return null;
        }

        $days = [];
        foreach ($daily['time'] as $index => $date) {
            $temperature = $daily['temperature_2m_max'][$index] ?? null;
            $rainProbability = $daily['precipitation_probability_max'][$index] ?? null;
            $precipitation = $daily['precipitation_sum'][$index] ?? null;
            $windSpeed = $daily['wind_speed_10m_max'][$index] ?? null;

            if (
                !is_string($date)
                || !is_numeric($temperature)
                || !is_numeric($rainProbability)
                || !is_numeric($precipitation)
                || !is_numeric($windSpeed)
            ) {
                continue;
            }

            $days[] = [
                'date' => $date,
                'temperature' => (int) round((float) $temperature),
                'rainProbability' => (int) round((float) $rainProbability),
                'precipitation' => (float) $precipitation,
                'windSpeed' => (int) round((float) $windSpeed),
            ];
        }

        if ($days === []) {
            return null;
        }

        return [
            'cachedAt' => time(),
            'location' => is_string($location['navn'] ?? null) ? $location['navn'] : $postcode,
            'days' => $days,
        ];
    }

    /** @param array{cachedAt:int,location:string,days:list<array<string,mixed>>} $cached */
    private function forecastFor(array $cached, DateTimeImmutable $now): ?array
    {
        $showTomorrow = (int) $now->format('G') >= self::EVENING_HOUR;
        $targetDate = ($showTomorrow ? $now->modify('+1 day') : $now)->format('Y-m-d');
        $day = null;

        foreach ($cached['days'] as $candidate) {
            if (($candidate['date'] ?? null) === $targetDate) {
                $day = $candidate;
                break;
            }
        }

        if ($day === null) {
            return null;
        }

        $temperature = (int) $day['temperature'];
        $rainProbability = (int) $day['rainProbability'];
        $precipitation = (float) $day['precipitation'];
        $windSpeed = (int) $day['windSpeed'];
        $isRainy = $rainProbability > 40 || $precipitation >= 0.3;
        $isGoodDryingWeather = $rainProbability <= 25
            && $precipitation < 0.3
            && $temperature >= 10
            && $windSpeed <= self::MAX_GOOD_WIND_SPEED;

        return [
            'period' => I18n::translate($showTomorrow ? 'i morgen' : 'i dag'),
            'location' => $cached['location'],
            'temperature' => $temperature,
            'rainProbability' => $rainProbability,
            'windSpeed' => $windSpeed,
            'isGoodDryingWeather' => $isGoodDryingWeather,
            'variant' => $isGoodDryingWeather ? 'clear' : ($isRainy ? 'rain' : 'mixed'),
            'recommendation' => I18n::translate(
                $this->recommendation($isGoodDryingWeather, $isRainy, $temperature, $windSpeed)
            ),
        ];
    }

    private function recommendation(bool $isGoodDryingWeather, bool $isRainy, int $temperature, int $windSpeed): string
    {
        if ($isGoodDryingWeather) {
            return 'Det ser lovende ud til udendørs tørring – måske kan tørretumbleren få en fridag.';
        }

        if ($isRainy) {
            return 'Der er risiko for regn, så hold øje med tøjet eller tør det indendørs.';
        }

        if ($windSpeed > self::MAX_GOOD_WIND_SPEED) {
            return 'Det bliver blæsende. Tøjet tørrer hurtigt, men husk ekstra tøjklemmer.';
        }

        if ($temperature < 10) {
            return 'Det bliver køligt, så tøjet tørrer nok langsommere udenfor.';
        }

        return 'Tørreforholdene er blandede. Tjek vejret igen, før du hænger tøjet ud.';
    }

    /** @return array<string,mixed>|null */
    private function requestJson(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
                'user_agent' => 'Vaskekalender/1.0',
            ],
        ]);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }
}
