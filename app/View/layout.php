<?php

declare(strict_types=1);

use function LaundryBooking\Support\current_locale;
use function LaundryBooking\Support\e;
use function LaundryBooking\Support\t;

require_once __DIR__ . '/helpers.php';

function asset_url(string $path): string
{
    $file = dirname(__DIR__, 2) . '/public' . $path;

    return $path . '?v=' . (string) (is_file($file) ? filemtime($file) : 1);
}

/**
 * Renders the opening HTML shell shared by all pages.
 *
 * @param array<string,string> $bodyData data-* attributes for the body, without the "data-" prefix
 */
function layout_start(string $title, bool $showNav = true, string $bodyClass = '', array $bodyData = []): void
{
    $isKiosk = str_contains($bodyClass, 'page-kiosk');
    $isCalendar = str_contains($bodyClass, 'page-calendar');
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $requestPath = is_string($requestPath) ? $requestPath : '/';
    $languageQuery = $_GET;
    $languageQuery['lang'] = 'da';
    $danishUrl = $requestPath . '?' . http_build_query($languageQuery);
    $languageQuery['lang'] = 'en';
    $englishUrl = $requestPath . '?' . http_build_query($languageQuery);
    $bodyAttributes = '';
    foreach ($bodyData as $name => $value) {
        $bodyAttributes .= ' data-' . e($name) . '="' . e($value) . '"';
    }
    ?><!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e(t($title)) ?> – <?= e(t('Vaskekalender')) ?></title>
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#faf9f4">
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= e(t('Vaskekalender')) ?>">
    <link rel="icon" href="/assets/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="stylesheet" href="<?= e(asset_url('/assets/app.css')) ?>">
<?php if ($isKiosk): ?>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/kiosk.css')) ?>">
<?php endif; ?>
<?php if ($isCalendar): ?>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/calendar.css')) ?>">
    <noscript><link rel="stylesheet" href="<?= e(asset_url('/assets/noscript.css')) ?>"></noscript>
<?php endif; ?>
    <script type="module" src="<?= e(asset_url('/assets/app.js')) ?>"></script>
</head>
<body class="<?= e($bodyClass) ?>"<?= $bodyAttributes ?>>
<?php include __DIR__ . '/partials/icons.php'; ?>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="/calendar.php" aria-label="<?= e(t('Vaskekalender – gå til kalenderen')) ?>">
            <span class="brand-mark" aria-hidden="true"><?= icon('washer') ?></span>
            <span class="brand-copy">
                <span class="brand-title"><?= e(t('Vaskekalender')) ?></span>
                <span class="brand-subtitle"><?= e(t('Book en tid i vaskerummet')) ?></span>
            </span>
        </a>
        <div class="header-actions">
            <nav class="language-switch" aria-label="Language / Sprog">
<?php if (current_locale() === 'da'): ?>
                <span class="language-option is-current" lang="da" aria-current="true">DA</span>
                <a class="language-option" href="<?= e($englishUrl) ?>" lang="en" hreflang="en">EN</a>
<?php else: ?>
                <a class="language-option" href="<?= e($danishUrl) ?>" lang="da" hreflang="da">DA</a>
                <span class="language-option is-current" lang="en" aria-current="true">EN</span>
<?php endif; ?>
            </nav>
<?php if ($showNav): ?>
            <nav class="site-nav" aria-label="<?= e(t('Primær navigation')) ?>">
<?php if (!$isCalendar): ?>
                <a class="nav-link nav-calendar" href="/calendar.php">
                    <?= icon('calendar') ?>
                    <span><?= e(t('Kalender')) ?></span>
                </a>
<?php endif; ?>
                <a class="nav-link nav-logout" href="/logout.php" aria-label="<?= e(t('Log ud')) ?>">
                    <?= icon('logout') ?>
                    <span><?= e(t('Log ud')) ?></span>
                </a>
            </nav>
<?php endif; ?>
        </div>
    </div>
</header>
<main class="site-main">
<?php
    $error = flash_get('error');
    $success = flash_get('success');

    if ($error !== null) {
        echo '<p class="alert alert-error" role="alert">' . e($error) . '</p>';
    }

    if ($success !== null) {
        echo '<p class="alert alert-success" role="status">' . e($success) . '</p>';
    }
}

/**
 * Renders the closing HTML shared by all pages.
 */
function layout_end(): void
{
    ?>
</main>
</body>
</html>
<?php
}
