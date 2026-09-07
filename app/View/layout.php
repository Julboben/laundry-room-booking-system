<?php

declare(strict_types=1);

use function LaundryBooking\Support\current_locale;
use function LaundryBooking\Support\e;
use function LaundryBooking\Support\t;

require_once __DIR__ . '/helpers.php';

/**
 * Renders the opening HTML shell shared by all resident-facing pages.
 */
function layout_start(string $title, bool $showNav = true, string $bodyClass = ''): void
{
    $assetDirectory = dirname(__DIR__, 2) . '/public/assets';
    $appCssVersion = (string) (filemtime($assetDirectory . '/app.css') ?: 1);
    $kioskCssVersion = (string) (filemtime($assetDirectory . '/kiosk.css') ?: 1);
    $calendarCssVersion = (string) (filemtime($assetDirectory . '/calendar.css') ?: 1);
    $appJsVersion = (string) (filemtime($assetDirectory . '/app.js') ?: 1);
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $requestPath = is_string($requestPath) ? $requestPath : '/';
    $languageQuery = $_GET;
    $languageQuery['lang'] = 'da';
    $danishUrl = $requestPath . '?' . http_build_query($languageQuery);
    $languageQuery['lang'] = 'en';
    $englishUrl = $requestPath . '?' . http_build_query($languageQuery);
    ?><!DOCTYPE html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t($title)) ?> – <?= e(t('Vaskekalender')) ?></title>
    <link rel="stylesheet" href="/assets/app.css?v=<?= e($appCssVersion) ?>">
<?php if (str_contains($bodyClass, 'page-kiosk')): ?>
    <link rel="stylesheet" href="/assets/kiosk.css?v=<?= e($kioskCssVersion) ?>">
<?php endif; ?>
<?php if (str_contains($bodyClass, 'page-calendar')): ?>
    <link rel="stylesheet" href="/assets/calendar.css?v=<?= e($calendarCssVersion) ?>">
<?php endif; ?>
    <script src="/assets/app.js?v=<?= e($appJsVersion) ?>" defer></script>
</head>
<body class="<?= e($bodyClass) ?>">
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="/calendar.php" aria-label="<?= e(t('Vaskekalender – gå til kalenderen')) ?>">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 48 48" role="img">
                    <rect x="10" y="5" width="28" height="38" rx="4"></rect>
                    <path d="M10 14h28"></path>
                    <circle cx="24" cy="28" r="9"></circle>
                    <circle cx="24" cy="28" r="6"></circle>
                    <circle cx="16" cy="10" r="1"></circle>
                    <path d="M30 10h3"></path>
                </svg>
            </span>
            <span class="brand-copy">
                <span class="brand-title"><?= e(t('Vaskekalender')) ?></span>
                <span class="brand-subtitle"><?= e(t('Book en tid i vaskerummet')) ?></span>
            </span>
        </a>
        <div class="header-actions">
            <nav class="language-switch" aria-label="Language / Sprog">
                <a href="<?= e($danishUrl) ?>" lang="da"<?= current_locale() === 'da' ? ' aria-current="true"' : '' ?>>DA</a>
                <span aria-hidden="true">/</span>
                <a href="<?= e($englishUrl) ?>" lang="en"<?= current_locale() === 'en' ? ' aria-current="true"' : '' ?>>EN</a>
            </nav>
<?php if ($showNav): ?>
            <nav class="site-nav" aria-label="<?= e(t('Primær navigation')) ?>">
                <a class="nav-calendar" href="/calendar.php">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2v3M18 2v3M3 9h18M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"></path></svg>
                    <span><?= e(t('Kalender')) ?></span>
                </a>
                <a class="nav-logout" href="/logout.php">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"></path></svg>
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
 * Renders the closing HTML shared by all resident-facing pages.
 */
function layout_end(): void
{
    ?>
</main>
</body>
</html>
<?php
}
