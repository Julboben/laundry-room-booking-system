<?php

declare(strict_types=1);

namespace LaundryBooking\Support;

/**
 * Escape a value for safe HTML output. Booking names and any other
 * user-supplied data must always be passed through this helper before
 * being echoed into a template.
 */
function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Prevent spreadsheet applications from interpreting exported text as formulas.
 */
function spreadsheet_safe(?string $value): string
{
    $value ??= '';

    return preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) === 1
        ? "'" . $value
        : $value;
}
