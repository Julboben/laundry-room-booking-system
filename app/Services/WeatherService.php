<?php

declare(strict_types=1);

namespace LaundryBooking\Services;

use LaundryBooking\Support\DateHelper;
use LaundryBooking\Support\I18n;

/**
 * Fetches and caches a simple outdoor drying forecast.
 * Weather failures are deliberately non-fatal to the booking flow.
 */
final class WeatherService
{
    private const int CACHE_TTL_SECONDS = 1800;
    private const int STALE_CACHE_TTL_SECONDS = 21600;

    public function __construct(
        private readonly string $cacheDirectory
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
     *   recommendation:string
     * }|null
     */
    public function getDryingForecast(string $postcode): ?array
    {
        if (preg_match('/^\d{4}$/', $postcode) !== 1) {
            return null;
        }

        $cacheFile = rtrim($this->cacheDirectory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'weather-' . $postcode . '.json';
        $cached = $this->readCache($cacheFile);

        if ($cached !== null && $cached['cachedAt'] >= time() - self::CACHE_TTL_SECONDS) {
            return $this->localizeForecast($cached['forecast']);
        }

        $forecast = $this->fetchForecast($postcode);

        if ($forecast !== null) {
            $this->writeCache($cacheFile, $forecast);
            return $this->localizeForecast($forecast);
        }

        if ($cached !== null && $cached['cachedAt'] >= time() - self::STALE_CACHE_TTL_SECONDS) {
            return $this->localizeForecast($cached['forecast']);
        }

        return null;
    }

    /**
     * @return array{cachedAt:int,forecast:array<string,mixed>}|null
     */
    private function readCache(string $cacheFile): ?array
    {
        if (!is_file($cacheFile)) {
            return null;
        }

        $contents = @file_get_contents($cacheFile);
        if ($contents === false) {
            return null;
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded) || !isset($decoded['cachedAt'], $decoded['forecast'])) {
            return null;
        }

        if (!is_int($decoded['cachedAt']) || !is_array($decoded['forecast'])) {
            return null;
        }

        return $decoded;
    }

    /**
     * @return array<string,mixed>|null
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
            'daily' => 'weather_code,temperature_2m_max,precipitation_sum,precipitation_probability_max,wind_speed_10m_max',
            'timezone' => 'Europe/Copenhagen',
            'forecast_days' => 2,
        ]);
        $weather = $this->requestJson('https://api.open-meteo.com/v1/forecast?' . $query);
        $daily = $weather['daily'] ?? null;

        if (!is_array($daily)) {
            return null;
        }

        $tomorrow = (int) DateHelper::now()->format('G') >= 18;
        $index = $tomorrow ? 1 : 0;
        $temperature = $daily['temperature_2m_max'][$index] ?? null;
        $rainProbability = $daily['precipitation_probability_max'][$index] ?? null;
        $precipitation = $daily['precipitation_sum'][$index] ?? null;
        $windSpeed = $daily['wind_speed_10m_max'][$index] ?? null;

        if (!is_numeric($temperature) || !is_numeric($rainProbability) || !is_numeric($precipitation) || !is_numeric($windSpeed)) {
            return null;
        }

        $temperature = (int) round((float) $temperature);
        $rainProbability = (int) round((float) $rainProbability);
        $precipitation = (float) $precipitation;
        $windSpeed = (int) round((float) $windSpeed);
        $isGoodDryingWeather = $rainProbability <= 25
            && $precipitation < 0.3
            && $temperature >= 10
            && $windSpeed <= 35;

        return [
            'period' => $tomorrow ? 'i morgen' : 'i dag',
            'location' => is_string($location['navn'] ?? null) ? $location['navn'] : $postcode,
            'temperature' => $temperature,
            'rainProbability' => $rainProbability,
            'windSpeed' => $windSpeed,
            'isGoodDryingWeather' => $isGoodDryingWeather,
            'recommendation' => $this->recommendation(
                $isGoodDryingWeather,
                $rainProbability,
                $precipitation,
                $temperature,
                $windSpeed
            ),
        ];
    }

    /** @param array<string,mixed> $forecast */
    private function localizeForecast(array $forecast): array
    {
        if (is_string($forecast['period'] ?? null)) {
            $forecast['period'] = I18n::translate($forecast['period']);
        }

        if (is_string($forecast['recommendation'] ?? null)) {
            $forecast['recommendation'] = I18n::translate($forecast['recommendation']);
        }

        return $forecast;
    }

    private function recommendation(
        bool $isGoodDryingWeather,
        int $rainProbability,
        float $precipitation,
        int $temperature,
        int $windSpeed
    ): string {
        if ($isGoodDryingWeather) {
            return 'Det ser lovende ud til udendørs tørring – måske kan tørretumbleren få en fridag.';
        }

        if ($rainProbability > 40 || $precipitation >= 0.3) {
            return 'Der er risiko for regn, så hold øje med tøjet eller tør det indenfor.';
        }

        if ($windSpeed > 35) {
            return 'Det bliver blæsende. Tøjet kan tørre hurtigt, men sørg for at fastgøre det godt.';
        }

        if ($temperature < 10) {
            return 'Det bliver køligt, så tøjet vil sandsynligvis tørre langsommere udenfor.';
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

    /** @param array<string,mixed> $forecast */
    private function writeCache(string $cacheFile, array $forecast): void
    {
        if (!is_dir($this->cacheDirectory) && !@mkdir($this->cacheDirectory, 0775, true) && !is_dir($this->cacheDirectory)) {
            return;
        }

        $payload = json_encode([
            'cachedAt' => time(),
            'forecast' => $forecast,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload !== false) {
            @file_put_contents($cacheFile, $payload, LOCK_EX);
        }
    }
}
