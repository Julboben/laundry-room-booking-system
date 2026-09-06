<?php

declare(strict_types=1);

use function LaundryBooking\Support\e;

require_once __DIR__ . '/helpers.php';

/**
 * Renders the opening HTML shell shared by all resident-facing pages.
 */
function layout_start(string $title, bool $showNav = true): void
{
    ?><!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> – Vaskekalender</title>
    <link rel="stylesheet" href="/assets/app.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="/calendar.php" aria-label="Vaskekalender – gå til kalenderen">
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
                <span class="brand-title">Vaskekalender</span>
                <span class="brand-subtitle">Book en tid i vaskerummet</span>
            </span>
        </a>
<?php if ($showNav): ?>
        <nav class="site-nav" aria-label="Primær navigation">
            <a class="nav-calendar" href="/calendar.php">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2v3M18 2v3M3 9h18M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"></path></svg>
                Kalender
            </a>
            <a class="nav-logout" href="/logout.php">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"></path></svg>
                Log ud
            </a>
        </nav>
<?php endif; ?>
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
<footer class="site-footer">
    <p>Vaskekalender &middot; Kun til intern brug for ejendommens beboere</p>
</footer>
</body>
</html>
<?php
}
