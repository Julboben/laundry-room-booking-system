<?php

declare(strict_types=1);

use function LaundryBooking\Support\e;

/**
 * Decorative clothesline scene. Colours come from the --illo-* custom
 * properties in app.css; $variant selects the sky: clear, mixed, rain or tip.
 */
function illustration(string $variant = 'tip', string $class = ''): string
{
    $variant = in_array($variant, ['clear', 'mixed', 'rain', 'tip'], true) ? $variant : 'tip';
    $sky = match ($variant) {
        'clear' => '<g class="illo-sun">'
            . '<circle cx="196" cy="24" r="12"/>'
            . '<path class="illo-rays" d="M196 4v4M196 40v4M176 24h4M212 24h4M182 10l3 3M207 35l3 3M210 10l-3 3M185 35l-3 3"/>'
            . '</g>',
        'mixed' => '<g class="illo-sun"><circle cx="186" cy="20" r="10"/></g>'
            . '<path class="illo-cloud" d="M182 40h30a9 9 0 0 0 0-18 13 13 0 0 0-24.5 3.5A7.5 7.5 0 0 0 182 40Z"/>',
        'rain' => '<path class="illo-cloud illo-cloud-rain" d="M172 32h36a10 10 0 0 0 0-20 14 14 0 0 0-26.5 3.5A8.3 8.3 0 0 0 172 32Z"/>'
            . '<path class="illo-drops" d="M180 38l-2 5M192 38l-2 5M204 38l-2 5"/>',
        default => '<path class="illo-bird" d="M150 20q4-4 8 0q4-4 8 0M170 30q3-3 6 0q3-3 6 0"/>',
    };

    return '<svg class="' . e(trim('illustration illustration-' . $variant . ' ' . $class)) . '" viewBox="0 0 240 160" aria-hidden="true" focusable="false">'
        . '<ellipse class="illo-ground" cx="120" cy="150" rx="112" ry="7"/>'
        . $sky
        . '<rect class="illo-post" x="21" y="38" width="6" height="112" rx="3"/>'
        . '<rect class="illo-post" x="213" y="38" width="6" height="112" rx="3"/>'
        . '<path class="illo-line" d="M24 50Q120 94 216 50"/>'
        . '<g class="illo-item illo-shirt" transform="translate(70 66)"><g class="illo-sway">'
        . '<path class="illo-cloth" d="M-8 0-22 8l4 13 6-3v27h24V18l6 3 4-13-14-8q-8 8-16 0Z"/>'
        . '<path class="illo-detail" d="M-4 26h8"/>'
        . '<rect class="illo-peg" x="-10" y="-5" width="4" height="9" rx="1.5"/>'
        . '<rect class="illo-peg" x="6" y="-5" width="4" height="9" rx="1.5"/>'
        . '</g></g>'
        . '<g class="illo-item illo-towel" transform="translate(124 72)"><g class="illo-sway">'
        . '<rect class="illo-cloth" x="-18" y="0" width="36" height="50" rx="3"/>'
        . '<path class="illo-stripe" d="M-18 36h36M-18 42h36"/>'
        . '<rect class="illo-peg" x="-14" y="-5" width="4" height="9" rx="1.5"/>'
        . '<rect class="illo-peg" x="10" y="-5" width="4" height="9" rx="1.5"/>'
        . '</g></g>'
        . '<g class="illo-item illo-sock" transform="translate(170 66)"><g class="illo-sway">'
        . '<path class="illo-cloth" d="M-5 0H5v18l8 6a4.5 4.5 0 0 1-5 7.5L-2 25q-3-2-3-6Z"/>'
        . '<path class="illo-detail" d="M-5 5h10"/>'
        . '<rect class="illo-peg" x="-2" y="-5" width="4" height="9" rx="1.5"/>'
        . '</g></g>'
        . '<g class="illo-item illo-sock illo-sock-2" transform="translate(190 60)"><g class="illo-sway">'
        . '<path class="illo-cloth" d="M-5 0H5v18l8 6a4.5 4.5 0 0 1-5 7.5L-2 25q-3-2-3-6Z"/>'
        . '<path class="illo-detail" d="M-5 5h10"/>'
        . '<rect class="illo-peg" x="-2" y="-5" width="4" height="9" rx="1.5"/>'
        . '</g></g>'
        . '</svg>';
}
