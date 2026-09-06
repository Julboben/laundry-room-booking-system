<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/View/layout.php';

use LaundryBooking\Auth\ResidentAccess;
use LaundryBooking\Database\Connection;
use LaundryBooking\Http\Csrf;
use LaundryBooking\Http\Request;
use LaundryBooking\Http\Response;
use LaundryBooking\Models\Setting;
use LaundryBooking\Security\RateLimiter;
use LaundryBooking\Services\SettingsService;

use function LaundryBooking\Support\e;

$pdo = Connection::get();
$settingsService = new SettingsService(new Setting($pdo));
$residentAccess = new ResidentAccess($settingsService, new RateLimiter($pdo), Request::ip());

if ($residentAccess->isAuthenticated()) {
    Response::redirect('/calendar.php');
}

$error = null;

if (Request::isPost()) {
    if (!Csrf::verify(Request::post('csrf_token'))) {
        $error = 'Sikkerhedstjek fejlede. Prøv igen.';
    } elseif ($residentAccess->isLockedOut()) {
        $minutes = (int) ceil($residentAccess->lockoutRemainingSeconds() / 60);
        $error = "For mange forsøg. Prøv igen om ca. {$minutes} minutter.";
    } else {
        $code = Request::post('property_code', '') ?? '';

        if ($residentAccess->attempt($code)) {
            Response::redirect('/calendar.php');
        }

        $error = 'Koden er ikke korrekt. Prøv igen.';
    }
}

layout_start('Adgang', showNav: false, bodyClass: 'page-kiosk page-access');
?>
<section class="card kiosk-card access-card">
    <h2>Ejendomskode</h2>
    <p>Indtast ejendommens fælles adgangskode for at se og booke tider i vaskekalenderen.</p>
    <?php if ($error !== null): ?>
        <p class="alert alert-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" action="/access.php" novalidate>
        <?= Csrf::field() ?>
        <label for="property_code">Ejendomskode</label>
        <input
            type="password"
            id="property_code"
            name="property_code"
            autocomplete="off"
            inputmode="numeric"
            required
        >
        <button type="submit" class="btn btn-primary">Gem</button>
    </form>
</section>
<?php
layout_end();
