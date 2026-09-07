<?php

declare(strict_types=1);
/**
 * Shared bootstrap for every public entry point. Loads the autoloader,
 * environment configuration, starts a secure session, and applies the
 * recommended security headers.
 */

use LaundryBooking\Http\Response;
use LaundryBooking\Support\Env;
use LaundryBooking\Support\I18n;

require __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/../.env');

date_default_timezone_set(Env::get('APP_TIMEZONE', 'Europe/Copenhagen') ?? 'Europe/Copenhagen');

$appDebug = Env::getBool('APP_DEBUG', false);

error_reporting(E_ALL);

if ($appDebug) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

$sessionName = Env::get('SESSION_NAME', 'laundry_booking_session') ?? 'laundry_booking_session';
session_name($sessionName);

$isHttps = (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
    || (
        Env::getBool('TRUST_PROXY_HEADERS', false)
        && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
    );

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $isHttps,
]);
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$requestedLocale = $_GET['lang'] ?? null;
if (is_string($requestedLocale) && in_array($requestedLocale, ['da', 'en'], true)) {
    $_SESSION['locale'] = $requestedLocale;
}
I18n::setLocale(is_string($_SESSION['locale'] ?? null) ? $_SESSION['locale'] : 'da');

Response::securityHeaders();
if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

set_exception_handler(static function (\Throwable $exception) use ($appDebug): void {
    error_log((string) $exception);
    http_response_code(500);

    if ($appDebug) {
        echo '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8') . '</pre>';
        return;
    }

    echo I18n::translate('Der opstod en uventet fejl. Prøv igen senere.');
});
