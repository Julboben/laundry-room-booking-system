<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use LaundryBooking\Database\Connection;
use LaundryBooking\Models\Setting;
use LaundryBooking\Services\SettingsService;
use LaundryBooking\Services\WeatherService;
use LaundryBooking\Support\Env;

Env::load(__DIR__ . '/../.env');

$settings = new Setting(Connection::get());
$postcode = (new SettingsService($settings))->getWeatherPostcode();

if ((new WeatherService($settings))->refresh($postcode)) {
    echo "Weather forecast for {$postcode} refreshed.\n";
    exit(0);
}

fwrite(STDERR, "Could not refresh the weather forecast for {$postcode}.\n");
exit(1);
