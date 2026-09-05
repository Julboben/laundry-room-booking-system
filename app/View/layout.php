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
    <h1><a href="/calendar.php">Vaskekalender</a></h1>
<?php if ($showNav): ?>
    <nav>
        <a href="/calendar.php">Kalender</a>
        <a href="/logout.php">Log ud</a>
    </nav>
<?php endif; ?>
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
    <p>Vaskekalender &ndash; kun til intern brug for ejendommens beboere.</p>
</footer>
</body>
</html>
<?php
}
